<?php

namespace App\Modules\Utilities\SupermarketComparator\Services;

use App\Modules\Utilities\SupermarketComparator\Services\Stores\AsyncStoreClientInterface;
use App\Modules\Utilities\SupermarketComparator\Services\Stores\MetroClient;
use App\Modules\Utilities\SupermarketComparator\Services\Stores\PlazaVeaClient;
use App\Modules\Utilities\SupermarketComparator\Services\Stores\StoreClientInterface;
use App\Modules\Utilities\SupermarketComparator\Services\Stores\TottusClient;
use App\Modules\Utilities\SupermarketComparator\Services\Stores\WongClient;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SupermarketComparatorService
{
    protected Normalizer $normalizer;
    protected AmbiguityDetector $ambiguityDetector;
    protected EquivalenceService $equivalence;
    protected BrandCatalog $brandCatalog;
    protected RequestGuard $requestGuard;

    /** @var StoreClientInterface[] */
    protected array $stores;

    public function __construct(
        PlazaVeaClient $plazaVea,
        TottusClient $tottus,
        MetroClient $metro,
        WongClient $wong,
    ) {
        $this->normalizer = new Normalizer();
        $this->ambiguityDetector = new AmbiguityDetector();
        $this->equivalence = new EquivalenceService();
        $this->brandCatalog = new BrandCatalog();
        $this->requestGuard = new RequestGuard();
        $this->stores = [$plazaVea, $tottus, $metro, $wong];
    }

    /**
     * @return array{
     *   context_token:string,
     *   query:string,
     *   location:string,
     *   suggested_refinement: array{brand:?string,size:?string,variant:?string,audience:?string,allow_similar:bool},
     *   ambiguity: array{is_ambiguous: bool, reasons: string[]},
     *   needs_refinement: bool,
     *   candidates: array<string, array<int, array>>,
     *   errors: array<string, string>
     * }
     */
    public function phase1Search(string $query, string $location = '', ?array $storeCodes = null): array
    {
        $signals = $this->normalizer->normalizeQuery($query);
        $suggestedRefinement = $this->normalizer->suggestRefinementFromQuery($query);
        $ambiguity = $this->ambiguityDetector->analyze($query, $signals);

        $errors = [];
        $errorStoreCodes = [];
        $candidatesByStore = [];
        $promises = [];
        $asyncStores = [];
        $asyncCacheKeys = [];

        $stores = $this->activeStores($storeCodes);

        foreach ($stores as $store) {
            if (!($store instanceof AsyncStoreClientInterface)) {
                continue;
            }

            $code = $store->storeCode();
            try {
                $key = 'smc:v1:' . $code . ':' . md5($signals['normalized'] . '|' . $location);

                $items = Cache::get($key);
                if ($items !== null && (config('app.debug') || env('SMC_DEBUG_LOG_CACHE'))) {
                    \Log::info('smc_cache_hit', ['store' => $code, 'key' => $key]);
                }
                if ($items !== null) {
                    $normalized = $this->normalizeCandidates($code, $items);
                    $deduped = $this->dedupe($normalized);
                    $candidatesByStore[$code] = $deduped;

                    try {
                        $this->brandCatalog->updateFromCandidates($code, $deduped);
                    } catch (\Throwable $e) {
                        // no-op
                    }

                    $this->requestGuard->onSuccess($code);
                    continue;
                }

                if (config('app.debug') || env('SMC_DEBUG_LOG_CACHE')) {
                    \Log::info('smc_cache_miss', ['store' => $code, 'key' => $key]);
                }
                if ($this->requestGuard->isCircuitOpen($code)) {
                    throw new \RuntimeException('Tienda temporalmente pausada por errores repetidos; reintenta en unos minutos.');
                }

                if (!$this->requestGuard->allowRequest($code)) {
                    throw new \RuntimeException('Rate limit interno activado para proteger la tienda; intenta nuevamente en 1 minuto.');
                }

                $promises[$code] = $store->searchWideAsync($query, $location);
                $asyncStores[$code] = $store;
                $asyncCacheKeys[$code] = $key;
            } catch (\Throwable $e) {
                if (empty($retryAttempted[$code])) {
                    $retryAttempted[$code] = true;
                    try {
                        $this->requestGuard->backoffSleepIfNeeded($e->getMessage());
                        $items = $store->searchWide($query, $location);
                        $normalized = $this->normalizeCandidates($code, $items);
                        $deduped = $this->dedupe($normalized);
                        $candidatesByStore[$code] = $deduped;

                        try {
                            $this->brandCatalog->updateFromCandidates($code, $deduped);
                        } catch (\Throwable $e) {
                            // no-op
                        }

                        $this->requestGuard->onSuccess($code);
                        continue;
                    } catch (\Throwable $retryError) {
                        $e = $retryError;
                    }
                }

                $errors[$store->storeName()] = $e->getMessage();
                $errorStoreCodes[$code] = true;
                $candidatesByStore[$code] = [];
                $this->requestGuard->backoffSleepIfNeeded($e->getMessage());
                $this->requestGuard->onFailure($code, $e->getMessage());
            }
        }

        $retryAttempted = [];
        foreach ($stores as $store) {
            if ($store instanceof AsyncStoreClientInterface) {
                continue;
            }
            $code = $store->storeCode();
            try {
                if ($code === 'plaza_vea') {
                    \Log::info('plazavea_phase1_enter', ['query' => $query, 'location' => $location]);
                }
                $key = 'smc:v1:' . $code . ':' . md5($signals['normalized'] . '|' . $location);

                // Cache hit: no cuenta para rate-limit.
                $items = Cache::get($key);
                if ($items !== null) {
                    if ($code === 'plaza_vea') {
                        \Log::info('plazavea_cache_hit', ['key' => $key]);
                    } elseif (config('app.debug') || env('SMC_DEBUG_LOG_CACHE')) {
                        \Log::info('smc_cache_hit', ['store' => $code, 'key' => $key]);
                    }
                }
                if ($items === null) {
                    if ($code === 'plaza_vea') {
                        \Log::info('plazavea_cache_miss', ['key' => $key]);
                    } elseif (config('app.debug') || env('SMC_DEBUG_LOG_CACHE')) {
                        \Log::info('smc_cache_miss', ['store' => $code, 'key' => $key]);
                    }
                    // Circuit breaker: si la tienda está "abierta" (bloqueos/errores repetidos), no insistir.
                    if ($this->requestGuard->isCircuitOpen($code)) {
                        throw new \RuntimeException('Tienda temporalmente pausada por errores repetidos; reintenta en unos minutos.');
                    }

                    // Rate-limit por tienda para evitar bloqueos por exceso de requests.
                    if (!$this->requestGuard->allowRequest($code)) {
                        throw new \RuntimeException('Rate limit interno activado para proteger la tienda; intenta nuevamente en 1 minuto.');
                    }

                    $items = Cache::remember($key, now()->addMinutes(10), fn () => $store->searchWide($query, $location));
                }

                $normalized = $this->normalizeCandidates($code, $items);
                $deduped = $this->dedupe($normalized);
                $candidatesByStore[$code] = $deduped;

                // Actualización en vivo del catálogo de marcas (best-effort).
                try {
                    $this->brandCatalog->updateFromCandidates($code, $deduped);
                } catch (\Throwable $e) {
                    // no-op
                }

                $this->requestGuard->onSuccess($code);
            } catch (\Throwable $e) {
                $errors[$store->storeName()] = $e->getMessage();
                $errorStoreCodes[$code] = true;
                $candidatesByStore[$code] = [];
                $this->requestGuard->backoffSleepIfNeeded($e->getMessage());
                $this->requestGuard->onFailure($code, $e->getMessage());
            }
        }

        if (!empty($promises)) {
            $results = Utils::settle($promises)->wait();
            foreach ($results as $code => $result) {
                $store = $asyncStores[$code] ?? null;
                if (!$store) {
                    continue;
                }

                if (($result['state'] ?? '') === 'fulfilled') {
                    $items = $result['value'];
                    $cacheKey = $asyncCacheKeys[$code] ?? null;
                    if (is_string($cacheKey) && $cacheKey !== '') {
                        Cache::put($cacheKey, $items, now()->addMinutes(10));
                    }

                    $normalized = $this->normalizeCandidates($code, $items);
                    $deduped = $this->dedupe($normalized);
                    $candidatesByStore[$code] = $deduped;

                    try {
                        $this->brandCatalog->updateFromCandidates($code, $deduped);
                    } catch (\Throwable $e) {
                        // no-op
                    }

                    $this->requestGuard->onSuccess($code);
                    continue;
                }

                $reason = $result['reason'] ?? null;
                $message = $reason instanceof \Throwable ? $reason->getMessage() : 'Error desconocido';
                if (empty($retryAttempted[$code])) {
                    $retryAttempted[$code] = true;
                    try {
                        $this->requestGuard->backoffSleepIfNeeded($message);
                        $items = $store->searchWide($query, $location);
                        $normalized = $this->normalizeCandidates($code, $items);
                        $deduped = $this->dedupe($normalized);
                        $candidatesByStore[$code] = $deduped;

                        try {
                            $this->brandCatalog->updateFromCandidates($code, $deduped);
                        } catch (\Throwable $e) {
                            // no-op
                        }

                        $this->requestGuard->onSuccess($code);
                        continue;
                    } catch (\Throwable $retryError) {
                        $message = $retryError->getMessage();
                    }
                }

                $errors[$store->storeName()] = $message;
                $errorStoreCodes[$code] = true;
                $candidatesByStore[$code] = [];
                $this->requestGuard->backoffSleepIfNeeded($message);
                $this->requestGuard->onFailure($code, $message);
            }
        }

        $contextToken = (string) Str::uuid();
        $context = [
            'query' => $query,
            'location' => $location,
            'signals' => $signals,
            'suggested_refinement' => $suggestedRefinement,
            'ambiguity' => $ambiguity,
            'candidates' => $candidatesByStore,
            'errors' => $errors,
            'created_at' => now()->toDateTimeString(),
        ];

        Cache::put($this->contextKey($contextToken), $context, now()->addMinutes(30));

        return [
            'context_token' => $contextToken,
            'query' => $query,
            'location' => $location,
            'suggested_refinement' => $suggestedRefinement,
            'ambiguity' => $ambiguity,
            'needs_refinement' => $ambiguity['is_ambiguous'],
            'candidates' => $candidatesByStore,
            'errors' => $errors,
            'error_store_codes' => array_keys($errorStoreCodes),
        ];
    }

    /**
     * Permite limitar temporalmente el scraping a una sola tienda vía env/config.
     *
     * @return StoreClientInterface[]
     */
    private function activeStores(?array $storeCodes = null): array
    {
        if (is_array($storeCodes) && !empty($storeCodes)) {
            $allowed = array_map(fn ($s) => strtolower(trim((string) $s)), $storeCodes);
            $filtered = array_values(array_filter(
                $this->stores,
                fn (StoreClientInterface $s) => in_array($s->storeCode(), $allowed, true)
            ));
            if (!empty($filtered)) {
                return $filtered;
            }
        }

        $only = config('services.supermarket_comparator.only_store');
        if (!is_string($only) || trim($only) === '') {
            return $this->stores;
        }

        $only = strtolower(trim($only));
        $filtered = array_values(array_filter($this->stores, fn (StoreClientInterface $s) => $s->storeCode() === $only));

        return $filtered ?: $this->stores;
    }

    /**
     * @return array{
     *   query: string,
     *   location: string,
     *   refinement: array,
     *   comparison: array{identical: array<int, array>, similar: array<int, array>},
     *   errors: array<string, string>
     * }
     */
    public function phase2Compare(string $contextToken, array $refinement): array
    {
        $context = Cache::get($this->contextKey($contextToken));
        if (!is_array($context)) {
            return [
                'query' => '',
                'location' => '',
                'refinement' => $refinement,
                'comparison' => ['identical' => [], 'similar' => []],
                'errors' => ['Contexto' => 'La sesión de comparación expiró. Vuelve a buscar.'],
            ];
        }

        $query = (string) ($context['query'] ?? '');
        $location = (string) ($context['location'] ?? '');
        $candidatesByStore = (array) ($context['candidates'] ?? []);
        $errors = (array) ($context['errors'] ?? []);
        $refinement['_query'] = $query;

        $identical = [];
        $similar = [];
        $combos = [];

        foreach ($candidatesByStore as $storeCode => $items) {
            foreach ((array) $items as $candidate) {
                if (($candidate['is_combo'] ?? false) === true) {
                    $combos[] = [
                        'store' => $storeCode,
                        'title' => $candidate['title'] ?? null,
                        'url' => $candidate['url'] ?? null,
                        'image_url' => $candidate['image_url'] ?? null,
                        'price' => $candidate['price'] ?? null,
                        'card_price' => $candidate['card_price'] ?? null,
                        'card_label' => $candidate['card_label'] ?? null,
                        'unit_price_text' => $candidate['unit_price_text'] ?? null,
                        'card_unit_price_text' => $candidate['card_unit_price_text'] ?? null,
                        'promo_text' => $candidate['promo_text'] ?? null,
                        'in_stock' => $candidate['in_stock'] ?? true,
                        'explain' => 'Combo / promoción condicionada (no comparable directamente)',
                    ];
                    continue;
                }

                $classification = $this->equivalence->classify($refinement, $candidate);

                $row = [
                    'store' => $storeCode,
                    'title' => $candidate['title'] ?? null,
                    'url' => $candidate['url'] ?? null,
                    'image_url' => $candidate['image_url'] ?? null,
                    'price' => $candidate['price'] ?? null,
                    'card_price' => $candidate['card_price'] ?? null,
                    'card_label' => $candidate['card_label'] ?? null,
                    'unit_price_text' => $candidate['unit_price_text'] ?? null,
                    'card_unit_price_text' => $candidate['card_unit_price_text'] ?? null,
                    'promo_text' => $candidate['promo_text'] ?? null,
                    'in_stock' => $candidate['in_stock'] ?? true,
                    'pack_text' => (($candidate['is_pack'] ?? false) && (($candidate['pack_count'] ?? 1) > 1)) ? ('Pack x' . (int) $candidate['pack_count']) : null,
                    'explain' => $classification['explain'],
                ];

                if ($classification['level'] === 'identical') {
                    $identical[] = $row;
                } elseif (($refinement['allow_similar'] ?? true) && $classification['level'] === 'similar') {
                    $similar[] = $row;
                }
            }
        }

        $identical = $this->sortByUnitPrice($identical);
        $similar = $this->sortByUnitPrice($similar);
        $combos = $this->sortByUnitPrice($combos);

        return [
            'query' => $query,
            'location' => $location,
            'refinement' => $refinement,
            'comparison' => [
                'identical' => $identical,
                'similar' => $similar,
                'combos' => $combos,
            ],
            'errors' => $errors,
        ];
    }

    private function contextKey(string $token): string
    {
        return 'smc:ctx:' . $token;
    }

    private function normalizeCandidates(string $storeCode, array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = (string) ($item['title'] ?? $item['name'] ?? '');
            $presentation = $this->normalizer->parsePresentation($title);
            if (($presentation['total_value'] ?? null) === null && !empty($item['size_text']) && is_string($item['size_text'])) {
                $presentation = $this->normalizer->parsePresentation($item['size_text']);
            }
            $price = isset($item['price']) ? (float) $item['price'] : null;
            $promoText = $item['promo_text'] ?? $this->normalizer->promoHintFromTitle($title);
            $cardPrice = isset($item['card_price']) ? (is_numeric($item['card_price']) ? (float) $item['card_price'] : null) : null;
            $cardLabel = isset($item['card_label']) && is_string($item['card_label']) ? trim($item['card_label']) : null;

            $out[] = [
                'store' => $storeCode,
                'title' => $title,
                'brand' => $item['brand'] ?? null,
                'variant' => $item['variant'] ?? $this->normalizer->inferVariant($title),
                'audience' => $item['audience'] ?? $this->normalizer->inferAudience($title),
                'price' => $price,
                'card_price' => $cardPrice,
                'card_label' => $cardLabel,
                'promo_text' => $promoText,
                'in_stock' => $item['in_stock'] ?? true,
                'url' => $item['url'] ?? null,
                'image_url' => $item['image_url'] ?? null,
                'pack_count' => $presentation['pack_count'],
                'is_pack' => $presentation['is_pack'],
                'is_combo' => $presentation['is_combo'],
                'total_value' => $presentation['total_value'],
                'base_unit' => $presentation['base_unit'],
                'unit_price_text' => $this->normalizer->unitPriceText($price, $presentation['total_value'], $presentation['base_unit']),
                'card_unit_price_text' => $this->normalizer->unitPriceText($cardPrice, $presentation['total_value'], $presentation['base_unit']),
                'raw' => $item,
            ];
        }

        return $out;
    }

    private function dedupe(array $candidates): array
    {
        $seen = [];
        $out = [];

        foreach ($candidates as $candidate) {
            $sig = $this->normalizer->signature($candidate);
            if ($sig === '' || isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;
            $out[] = $candidate;
        }

        return $out;
    }

    private function sortByUnitPrice(array $rows): array
    {
        usort($rows, function ($a, $b) {
            $ua = $this->extractUnitPrice($a['unit_price_text'] ?? null);
            $ub = $this->extractUnitPrice($b['unit_price_text'] ?? null);
            if ($ua === null && $ub === null) {
                return 0;
            }
            if ($ua === null) {
                return 1;
            }
            if ($ub === null) {
                return -1;
            }
            return $ua <=> $ub;
        });

        return $rows;
    }

    private function extractUnitPrice(?string $text): ?float
    {
        if ($text === null) {
            return null;
        }
        // "S/ 12.34 / kg"
        if (preg_match('/S\\/\\s*([0-9]+(?:\\.[0-9]+)?)/', str_replace(',', '.', $text), $m)) {
            return (float) $m[1];
        }
        return null;
    }
}

