<?php

namespace App\Modules\Utilities\SupermarketComparator\Services\Stores;

use App\Traits\BrowserSimulationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class PlazaVeaClient implements StoreClientInterface
{
    use BrowserSimulationTrait;

    protected Client $client;
    private ?CookieJar $cookieJar = null;
    private ?array $sessionHeaders = null;
    private bool $sessionBootstrapped = false;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 12,
            'http_errors' => false,
            'verify' => false,
        ]);
    }

    public function storeCode(): string
    {
        return 'plaza_vea';
    }

    public function storeName(): string
    {
        return 'Plaza Vea';
    }

    private function resetSession(): void
    {
        $this->cookieJar = null;
        $this->sessionHeaders = null;
        $this->sessionBootstrapped = false;
    }

    private function sessionOptions(string $url, string $logContext): array
    {
        if ($this->cookieJar === null) {
            $this->cookieJar = new CookieJar();
        }

        if ($this->sessionHeaders === null) {
            $this->sessionHeaders = $this->getBrowserHeaders($url);
        }

        $this->logBrowserIdentity($logContext, $url, $this->sessionHeaders, $this->cookieJar);

        return [
            'headers' => $this->sessionHeaders,
            'cookies' => $this->cookieJar,
        ];
    }

    private function bootstrapSession(string $baseUrl): void
    {
        if ($this->sessionBootstrapped) {
            return;
        }

        $homeUrl = rtrim($baseUrl, '/') . '/';
        $options = $this->sessionOptions($homeUrl, 'plazavea_bootstrap');
        $options['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $options['headers']['Referer'] = $homeUrl;
        $options['headers']['Origin'] = $baseUrl;

        $this->client->get($homeUrl, $options);
        $this->sessionBootstrapped = true;
    }

    private function normalizeForMatch(string $text): string
    {
        $text = Str::ascii($text);
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text) ?? $text;
        $text = preg_replace('/\\s+/', ' ', $text) ?? $text;
        return trim($text);
    }

    private function isDebugQuery(string $query): bool
    {
        $needle = $this->normalizeForMatch("Plátano Bizcocho BELL'S pk 10 und");
        $hay = $this->normalizeForMatch($query);
        return $needle !== '' && $hay === $needle;
    }

    /**
     * @return string[]
     */
    private function tokenizeQuery(string $query): array
    {
        $q = $this->normalizeForMatch($query);
        if ($q === '') {
            return [];
        }

        $stop = [
            'de', 'del', 'la', 'las', 'el', 'los', 'y', 'o', 'con', 'sin', 'para', 'por',
            'un', 'una', 'unos', 'unas', 'x',
            'estilo', 'botella',
        ];
        $stop = array_fill_keys($stop, true);

        $tokens = array_map('trim', explode(' ', $q));
        $tokens = array_values(array_filter($tokens, function ($t) use ($stop) {
            if ($t === '' || isset($stop[$t])) {
                return false;
            }
            if (is_numeric($t)) {
                return true;
            }
            return mb_strlen($t) >= 3;
        }));

        return array_values(array_unique($tokens));
    }

    private function titleMatchesQuery(string $title, array $tokens): bool
    {
        if (empty($tokens)) {
            return true;
        }

        $hay = ' ' . $this->normalizeForMatch($title) . ' ';
        if ($hay === '  ') {
            return false;
        }

        $matched = 0;
        foreach ($tokens as $t) {
            if (str_contains($hay, ' ' . $t . ' ')) {
                $matched++;
            }
        }

        $total = count($tokens);
        $minRatio = $total <= 2 ? 0.5 : 0.8;
        $ratio = $matched / $total;
        return $ratio >= $minRatio;
    }

    public function searchWide(string $query, string $location = ''): array
    {
        $baseUrl = rtrim(config('services.supermarket_comparator.plaza_vea_base_url', 'https://www.plazavea.com.pe'), '/');
        $this->bootstrapSession($baseUrl);

        if ($this->isDebugQuery($query)) {
            \Log::info('plazavea_debug_query_enter', ['query' => $query, 'base_url' => $baseUrl]);
        }

        $items = $this->fetchSearchJsonItems($query, $baseUrl);

        if (empty($items)) {
            $url = $baseUrl . '/search/?_query=' . rawurlencode($query);
            \Log::info('plazavea_search_start', ['query' => $query, 'url' => $url]);
            $finalUrl = $this->resolveSearchUrl($url, $baseUrl);
            if ($this->isDebugQuery($query)) {
                \Log::info('plazavea_debug_resolve', ['query' => $query, 'start_url' => $url, 'final_url' => $finalUrl]);
            }
            $html = $this->fetchSearchHtml($finalUrl, $baseUrl, $query);
            $items = $this->parseSearchHtml($html, $baseUrl);
            if (empty($items)) {
                $legacyUrl = $baseUrl . '/busca/?ft=' . rawurlencode($query);
                if ($this->isDebugQuery($query)) {
                    \Log::info('plazavea_debug_legacy_url', ['query' => $query, 'legacy_url' => $legacyUrl]);
                }
                \Log::info('plazavea_search_fallback', ['from' => $finalUrl, 'to' => $legacyUrl]);
                $legacyHtml = $this->fetchSearchHtml($legacyUrl, $baseUrl, $query);
                $items = $this->parseSearchHtml($legacyHtml, $baseUrl);
            }
        }

        $tokens = $this->tokenizeQuery($query);
        if (!empty($tokens)) {
            $before = count($items);
            $filtered = array_values(array_filter($items, fn ($it) => is_array($it) && $this->titleMatchesQuery((string) ($it['title'] ?? ''), $tokens)));
            if (!empty($filtered)) {
                $items = $filtered;
                if ((config('app.debug') || env('SMC_DEBUG_PLAZAVEA')) && $before !== count($items)) {
                    \Log::info('plazavea_query_filter', ['before' => $before, 'after' => count($items), 'query' => $query]);
                }
            } elseif (config('app.debug') || env('SMC_DEBUG_PLAZAVEA')) {
                \Log::info('plazavea_query_filter_empty', ['before' => $before, 'query' => $query]);
            }
        }

        return $items;
    }

    private function fetchSearchJsonItems(string $query, string $baseUrl): array
    {
        $url = $baseUrl . '/api/io/_v/api/intelligent-search/product_search?query=' . urlencode($query) . '&page=1&count=20';
        if ($this->isDebugQuery($query)) {
            \Log::info('plazavea_debug_api_url', ['query' => $query, 'url' => $url]);
        }
        $requestOptions = $this->sessionOptions($url, 'plazavea_search_api');
        $requestOptions['headers']['Origin'] = $baseUrl;
        $requestOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
        $requestOptions['headers']['Accept'] = 'application/json,text/plain,*/*';
        $requestOptions['headers']['Referer'] = $baseUrl . '/';

        $response = $this->client->get($url, $requestOptions);
        $status = $response->getStatusCode();
        if ($this->isDebugQuery($query)) {
            $bodySnippet = trim(substr((string) $response->getBody(), 0, 200));
            \Log::info('plazavea_debug_api_status', ['query' => $query, 'status' => $status, 'body_snippet' => $bodySnippet]);
        }
        \Log::info('plazavea_search_api_status', ['status' => $status, 'url' => $url]);
        if ($status !== 200) {
            return [];
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (!is_array($payload)) {
            return [];
        }

        $products = $payload['products'] ?? $payload;
        if (!is_array($products)) {
            return [];
        }

        $items = $this->buildItemsFromProducts($products, $baseUrl);
        \Log::info('plazavea_search_api_items', ['count' => count($items)]);
        return $items;
    }

    private function resolveSearchUrl(string $url, string $baseUrl): string
    {
        $requestOptions = $this->sessionOptions($url, 'plazavea_search_resolve');
        $requestOptions['headers']['Origin'] = $baseUrl;
        $requestOptions['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $requestOptions['headers']['Referer'] = $baseUrl . '/';
        $requestOptions['headers']['Accept-Encoding'] = 'identity';
        $requestOptions['allow_redirects'] = false;

        $response = $this->client->get($url, $requestOptions);
        $status = $response->getStatusCode();
        $locationHeader = $response->getHeaderLine('Location');
        \Log::info('plazavea_search_resolve', ['url' => $url, 'status' => $status, 'location' => $locationHeader]);

        if (in_array($status, [301, 302, 303, 307, 308], true)) {
            $location = $locationHeader;
            if ($location !== '') {
                if (!Str::startsWith($location, 'http')) {
                    $location = $baseUrl . (str_starts_with($location, '/') ? $location : ('/' . $location));
                }
                \Log::info('plazavea_search_redirect', ['from' => $url, 'to' => $location]);
                return $location;
            }
        }

        return $url;
    }

    private function fetchSearchHtml(string $url, string $baseUrl, string $query = ''): string
    {
        $requestOptions = $this->sessionOptions($url, 'plazavea_search_html');
        $requestOptions['headers']['Origin'] = $baseUrl;
        $requestOptions['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $requestOptions['headers']['Referer'] = $baseUrl . '/';
        $requestOptions['headers']['Accept-Encoding'] = 'identity';

        $response = $this->client->get($url, $requestOptions);
        $status = $response->getStatusCode();
        if ($status !== 200) {
            if ($this->isDebugQuery($query)) {
                $snippet = trim(substr((string) $response->getBody(), 0, 200));
                \Log::info('plazavea_debug_html_status', ['query' => $query, 'url' => $url, 'status' => $status, 'body_snippet' => $snippet]);
            }
            $this->resetSession();
            $this->bootstrapSession($baseUrl);

            $retryOptions = $this->sessionOptions($url, 'plazavea_search_html_retry');
            $retryOptions['headers']['Origin'] = $baseUrl;
            $retryOptions['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
            $retryOptions['headers']['Referer'] = $baseUrl . '/';
            $retryOptions['headers']['Accept-Encoding'] = 'identity';

            $response = $this->client->get($url, $retryOptions);
            $status = $response->getStatusCode();
        }

        if ($status !== 200) {
            $body = (string) $response->getBody();
            $snippet = trim(substr(preg_replace('/\\s+/', ' ', $body) ?? $body, 0, 140));
            throw new \RuntimeException('Plaza Vea: busqueda HTML no disponible (HTTP ' . $status . '). ' . ($snippet ? ('Respuesta: ' . $snippet) : ''));
        }

        $html = (string) $response->getBody();
        if (env('SMC_PLAZAVEA_DUMP_HTML')) {
            $dir = storage_path('app/plazavea_debug');
            File::ensureDirectoryExists($dir);
            $ts = now()->format('Ymd_His');
            @file_put_contents($dir . DIRECTORY_SEPARATOR . "search_{$ts}.html", $html);
        }
        return $html;
    }

    private function parseSearchHtml(string $html, string $baseUrl): array
    {
        $state = $this->extractStateJson($html);
        $products = $this->extractProductsFromState($state);
        if (!empty($products)) {
            return $this->buildItemsFromProducts($products, $baseUrl);
        }

        $crawler = new Crawler($html);
        $products = $this->extractProductsFromJsonLd($crawler);
        if (!empty($products)) {
            return $this->buildItemsFromProducts($products, $baseUrl);
        }

        $buscaUrl = $this->extractBuscapaginaUrl($html, $baseUrl);
        if ($buscaUrl) {
            \Log::info('plazavea_buscapagina_url', ['url' => $buscaUrl]);
            $buscaHtml = $this->fetchBuscapaginaHtml($buscaUrl, $baseUrl);
            $items = $this->extractProductsFromBuscapaginaHtml($buscaHtml, $baseUrl);
            if (!empty($items)) {
                return $items;
            }
        }

        return $this->extractProductsFromHtml($crawler, $baseUrl);
    }

    private function extractBuscapaginaUrl(string $html, string $baseUrl): ?string
    {
        $patterns = [
            "/(\\/buscapagina\\?[^\\\"']*PageNumber=)\\s*\\+\\s*pageclickednumber/i",
            "/(\\/buscapagina\\?[^\\\"']*PageNumber=)[^\\\"']*/i",
            "/\\/buscapagina\\?[^\\\"']+/i",
        ];

        $raw = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $raw = $m[1] ?? $m[0];
                break;
            }
        }

        if ($raw === null) {
            return null;
        }

        $raw = html_entity_decode($raw, ENT_QUOTES);
        $raw = preg_replace("/PageNumber=['\\\"]?\\s*\\+\\s*pageclickednumber/i", 'PageNumber=1', $raw) ?? $raw;

        if (str_contains($raw, 'PageNumber=') && !preg_match('/PageNumber=\\d+/', $raw)) {
            $raw .= '1';
        }
        if (!str_contains($raw, 'PageNumber=')) {
            $raw .= (str_contains($raw, '?') ? '&' : '?') . 'PageNumber=1';
        }

        if (!Str::startsWith($raw, 'http')) {
            $raw = $baseUrl . $raw;
        }

        return $raw;
    }

    private function fetchBuscapaginaHtml(string $url, string $baseUrl): string
    {
        $requestOptions = $this->sessionOptions($url, 'plazavea_buscapagina');
        $requestOptions['headers']['Origin'] = $baseUrl;
        $requestOptions['headers']['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $requestOptions['headers']['Referer'] = $baseUrl . '/';
        $requestOptions['headers']['Accept-Encoding'] = 'identity';

        $response = $this->client->get($url, $requestOptions);
        $status = $response->getStatusCode();
        if ($status !== 200) {
            throw new \RuntimeException('Plaza Vea: buscapagina no disponible (HTTP ' . $status . ').');
        }

        $html = (string) $response->getBody();
        if (env('SMC_PLAZAVEA_DUMP_HTML')) {
            $dir = storage_path('app/plazavea_debug');
            File::ensureDirectoryExists($dir);
            $ts = now()->format('Ymd_His');
            @file_put_contents($dir . DIRECTORY_SEPARATOR . "buscapagina_{$ts}.html", $html);
        }

        return $html;
    }

    /**
     * @return array<int, array>
     */
    private function extractProductsFromBuscapaginaHtml(string $html, string $baseUrl): array
    {
        $crawler = new Crawler($html);
        $items = [];

        $crawler->filter('li')->each(function (Crawler $node) use (&$items, $baseUrl) {
            $title = $this->firstText($node, [
                'a.productName',
                'a.product-name',
                'a.product-title',
                'a[title]',
            ]);
            $title = trim($title);
            if ($title === '') {
                return;
            }

            $url = $this->firstAttr($node, [
                'a.productName',
                'a.product-name',
                'a.product-title',
                'a[title]',
                'a[href]',
            ], 'href');
            if (is_string($url) && $url !== '' && !Str::startsWith($url, 'http')) {
                $url = $baseUrl . $url;
            }

            $image = $this->firstAttr($node, ['img'], 'data-src');
            if ($image === '') {
                $image = $this->firstAttr($node, ['img'], 'src');
            }

            $items[] = [
                'title' => $title,
                'brand' => null,
                'variant' => null,
                'audience' => null,
                'price' => null,
                'promo_text' => null,
                'in_stock' => true,
                'url' => $url ?: null,
                'image_url' => $image ?: null,
                'size_text' => null,
            ];
        });

        if (!empty($items)) {
            return $items;
        }

        $productIds = [];
        $crawler->filter('[data-prod]')->each(function (Crawler $node) use (&$productIds) {
            $id = $node->attr('data-prod');
            if (is_string($id)) {
                $id = trim($id);
                if ($id !== '') {
                    $productIds[] = $id;
                }
            }
        });

        $productIds = array_values(array_unique($productIds));
        if (empty($productIds)) {
            return [];
        }

        $products = $this->fetchProductsByIds($productIds, $baseUrl);
        if (empty($products)) {
            return [];
        }

        return $this->buildItemsFromProducts($products, $baseUrl);
    }

    private function firstText(Crawler $node, array $selectors): string
    {
        foreach ($selectors as $selector) {
            try {
                $text = $node->filter($selector)->first()->text();
                if (is_string($text) && trim($text) !== '') {
                    return $text;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return '';
    }

    private function firstAttr(Crawler $node, array $selectors, string $attr): string
    {
        foreach ($selectors as $selector) {
            try {
                $value = $node->filter($selector)->first()->attr($attr);
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return '';
    }

    private function extractStateJson(string $html): ?array
    {
        if (preg_match('/<script[^>]*id="__STATE__"[^>]*>(.*?)<\\/script>/s', $html, $m)) {
            $raw = trim($m[1]);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/window\\.__STATE__\\s*=\\s*({.*?})\\s*;\\s*<\\/script>/s', $html, $m)) {
            $raw = trim($m[1]);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array<int, array>
     */
    private function extractProductsFromState(?array $state): array
    {
        if (!is_array($state)) {
            return [];
        }

        $products = [];
        $this->walkState($state, $products);

        return $products;
    }

    private function walkState($node, array &$products): void
    {
        if (!is_array($node)) {
            return;
        }

        $hasName = isset($node['productName']) || isset($node['productTitle']);
        if ($hasName && (isset($node['items']) || isset($node['link']) || isset($node['linkText']))) {
            $products[] = $node;
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $this->walkState($child, $products);
            }
        }
    }

    /**
     * @return array<int, array>
     */
    private function extractProductsFromJsonLd(Crawler $crawler): array
    {
        $products = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function ($node) use (&$products) {
            $json = trim($node->text());
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                return;
            }

            $candidates = [];
            if (isset($decoded['@type']) && $decoded['@type'] === 'Product') {
                $candidates[] = $decoded;
            } elseif (isset($decoded[0]) && is_array($decoded[0])) {
                $candidates = $decoded;
            }

            foreach ($candidates as $product) {
                if (!is_array($product)) {
                    continue;
                }
                if (!isset($product['name'])) {
                    continue;
                }
                $products[] = [
                    'productName' => $product['name'],
                    'brand' => is_array($product['brand'] ?? null) ? ($product['brand']['name'] ?? null) : ($product['brand'] ?? null),
                    'link' => $product['url'] ?? null,
                    'items' => [],
                ];
            }
        });

        return $products;
    }

    /**
     * @return array<int, array>
     */
    private function extractProductsFromHtml(Crawler $crawler, string $baseUrl): array
    {
        $items = [];
        $crawler->filter('[data-product-name]')->each(function ($node) use (&$items, $baseUrl) {
            $title = trim((string) $node->attr('data-product-name'));
            if ($title === '') {
                return;
            }

            $brand = $node->attr('data-product-brand') ?: null;
            $url = null;
            if ($node->nodeName() === 'a') {
                $url = $node->attr('href');
            }
            if (is_string($url) && $url !== '' && !Str::startsWith($url, 'http')) {
                $url = $baseUrl . $url;
            }

            $items[] = [
                'title' => $title,
                'brand' => $brand,
                'variant' => null,
                'audience' => null,
                'price' => null,
                'promo_text' => null,
                'in_stock' => true,
                'url' => $url,
                'image_url' => null,
                'size_text' => null,
            ];
        });

        return $items;
    }

    /**
     * @return array<int, array>
     */
    private function buildItemsFromProducts(array $products, string $baseUrl): array
    {
        $items = [];
        $seen = [];

        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $title = (string) ($product['productName'] ?? $product['productTitle'] ?? '');
            if ($title === '') {
                continue;
            }

            $brand = $product['brand'] ?? ($product['brandName'] ?? null);
            $link = $product['link'] ?? ($product['linkText'] ?? null);
            $sizeText = null;
            $variant = null;

            $specGroups = $product['specificationGroups'] ?? null;
            if (is_array($specGroups)) {
                foreach ($specGroups as $group) {
                    if (!is_array($group) || !is_array($group['specifications'] ?? null)) {
                        continue;
                    }
                    foreach ($group['specifications'] as $spec) {
                        if (!is_array($spec)) {
                            continue;
                        }
                        $name = mb_strtolower((string) ($spec['name'] ?? $spec['originalName'] ?? ''));
                        $values = $spec['values'] ?? null;
                        $value0 = is_array($values) ? ($values[0] ?? null) : null;
                        $value0 = is_string($value0) ? trim($value0) : null;
                        if (!$value0) {
                            continue;
                        }
                        if ($sizeText === null && str_contains($name, 'contenido neto')) {
                            $sizeText = $value0;
                        }
                        if ($variant === null && (str_contains($name, 'sabor') || str_contains($name, 'variedad') || str_contains($name, 'contenido de grasa'))) {
                            $variant = $value0;
                        }
                    }
                }
            }

            $firstItem = is_array($product['items'] ?? null) ? ($product['items'][0] ?? null) : null;
            $imageUrl = null;
            if (is_array($firstItem) && is_array($firstItem['images'] ?? null)) {
                $img0 = $firstItem['images'][0] ?? null;
                if (is_array($img0) && isset($img0['imageUrl']) && is_string($img0['imageUrl'])) {
                    $imageUrl = $img0['imageUrl'];
                }
            }
            $unitMultiplier = is_array($firstItem) && is_numeric($firstItem['unitMultiplier'] ?? null)
                ? (float) $firstItem['unitMultiplier']
                : null;
            $measurementUnit = is_array($firstItem) && is_string($firstItem['measurementUnit'] ?? null)
                ? mb_strtolower((string) $firstItem['measurementUnit'])
                : null;
            $firstSeller = is_array($firstItem['sellers'] ?? null) ? ($firstItem['sellers'][0] ?? null) : null;
            $offer = is_array($firstSeller['commertialOffer'] ?? null) ? ($firstSeller['commertialOffer'] ?? null) : null;

            $price = isset($offer['Price']) ? (float) $offer['Price'] : null;
            $listPrice = isset($offer['ListPrice']) ? (float) $offer['ListPrice'] : null;
            $priceWithoutDiscount = $this->parseNumeric($offer['PriceWithoutDiscount'] ?? null);
            $promoTableDiscount = $this->parseNumeric($offer['PromotionalPriceTableItemsDiscount'] ?? null);
            $availableQty = isset($offer['AvailableQuantity']) ? (float) $offer['AvailableQuantity'] : null;

            $inStock = $availableQty === null ? true : ($availableQty > 0);
            $promoText = null;
            $cardPrice = null;
            $cardLabel = null;
            if ($listPrice !== null && $price !== null && $listPrice > $price) {
                $promoText = 'Antes S/ ' . number_format($listPrice, 2);
            }
            if (is_array($offer) && is_array($offer['teasers'] ?? null) && !empty($offer['teasers'])) {
                $names = [];
                foreach ($offer['teasers'] as $t) {
                    if (is_array($t) && isset($t['name']) && is_string($t['name'])) {
                        $names[] = trim($t['name']);
                    }
                }
                $names = array_values(array_filter(array_unique($names)));
                if (!empty($names)) {
                    $promoText = trim(($promoText ? ($promoText . ' ') : '') . implode(', ', $names));
                }
            }

            if ($priceWithoutDiscount !== null && $promoTableDiscount !== null) {
                $candidate = $priceWithoutDiscount - $promoTableDiscount;
                if ($candidate > 0 && $candidate < 50000) {
                    $cardPrice = round($candidate, 2);
                    $cardLabel = $cardLabel ?? 'Tarjeta';
                }
            }
            if (is_array($offer) && $cardPrice === null) {
                [$cardPrice, $cardLabel] = $this->extractCardPriceFromOffer($offer);
            }

            if ($cardPrice !== null
                && $unitMultiplier !== null
                && $unitMultiplier > 1
                && in_array($measurementUnit, ['kg', 'l'], true)
            ) {
                $source = $priceWithoutDiscount ?? $price;
                if ($source !== null) {
                    $discount = $source - $cardPrice;
                    if ($discount > 0) {
                        $scaled = $source - ($discount / $unitMultiplier);
                        if ($scaled > 0 && $scaled < $source) {
                            $cardPrice = round($scaled, 2);
                        }
                    }
                }
            }

            $url = null;
            if (is_string($link) && $link !== '') {
                if (Str::startsWith($link, 'http')) {
                    $url = $link;
                } else {
                    $url = $baseUrl . (str_starts_with($link, '/') ? $link : ('/' . $link));
                }
            }

            $sig = mb_strtolower($title) . '|' . ($url ?? '');
            if (isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;

            $items[] = [
                'title' => $title,
                'brand' => is_string($brand) ? $brand : null,
                'variant' => $variant,
                'audience' => null,
                'price' => $price,
                'card_price' => $cardPrice,
                'card_label' => $cardLabel,
                'promo_text' => $promoText,
                'in_stock' => $inStock,
                'url' => $url,
                'image_url' => $imageUrl,
                'size_text' => $sizeText,
            ];
        }

        return $items;
    }

    private function extractCardPriceFromOffer(array $offer): array
    {
        $candidates = [];
        $basePrice = $this->parseNumeric($offer['Price'] ?? null);
        $priceWithoutDiscount = $this->parseNumeric($offer['PriceWithoutDiscount'] ?? null);

        if ($basePrice !== null && is_array($offer['teasers'] ?? null) && !empty($offer['teasers'])) {
            foreach ($offer['teasers'] as $teaser) {
                if (!is_array($teaser)) {
                    continue;
                }

                $name = isset($teaser['name']) && is_string($teaser['name']) ? $teaser['name'] : '';
                if ($name === '' || !preg_match('/(tarjeta|oh|cencosud|cmr|prime)/i', $name)) {
                    continue;
                }

                $effects = is_array($teaser['effects'] ?? null) ? $teaser['effects'] : null;
                $params = is_array($effects['parameters'] ?? null) ? $effects['parameters'] : null;
                if (!is_array($params)) {
                    continue;
                }

                $percent = null;
                $promoDiscount = null;
                foreach ($params as $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $paramName = $p['name'] ?? null;
                    $value = $p['value'] ?? null;
                    if ($paramName === 'PercentualDiscount') {
                        $percent = $this->parseNumeric($value);
                    } elseif ($paramName === 'PromotionalPriceTableItemsDiscount') {
                        $promoDiscount = $this->parseNumeric($value);
                    }
                }

                if ($promoDiscount !== null && ($priceWithoutDiscount ?? $basePrice) !== null) {
                    $source = $priceWithoutDiscount ?? $basePrice;
                    $candidate = $source - $promoDiscount;
                    if ($candidate > 0 && $candidate < 50000) {
                        $candidates[] = ['price' => $candidate, 'label' => $this->extractCardLabel($name)];
                        continue;
                    }
                }

                if ($percent !== null && $basePrice !== null && $percent > 0 && $percent < 100) {
                    $candidate = $basePrice * (1.0 - ($percent / 100.0));
                    if ($candidate > 0 && $candidate < 50000) {
                        $candidates[] = ['price' => $candidate, 'label' => $this->extractCardLabel($name)];
                    }
                }
            }
        }

        $priceTags = $offer['PriceTags'] ?? ($offer['priceTags'] ?? null);
        if ($basePrice !== null && is_array($priceTags)) {
            foreach ($priceTags as $tag) {
                if (!is_array($tag)) {
                    continue;
                }
                $name = isset($tag['name']) && is_string($tag['name']) ? $tag['name'] : (isset($tag['Name']) && is_string($tag['Name']) ? $tag['Name'] : '');
                $value = $tag['value'] ?? ($tag['Value'] ?? null);

                if ($name === '' || !preg_match('/(tarjeta|oh|cencosud|cmr|prime)/i', $name)) {
                    continue;
                }
                $delta = $this->parseNumeric($value);
                if ($delta === null) {
                    continue;
                }
                if (abs($delta) > 1000 && $basePrice < 1000) {
                    $delta = $delta / 100.0;
                }

                $candidate = $basePrice + $delta;
                if ($candidate <= 0 || $candidate > 50000) {
                    continue;
                }

                $candidates[] = ['price' => $candidate, 'label' => $this->extractCardLabel($name)];
            }
        }

        if (empty($candidates)) {
            return [null, null];
        }

        usort($candidates, fn ($a, $b) => ($a['price'] <=> $b['price']));
        return [(float) $candidates[0]['price'], (string) $candidates[0]['label']];
    }

    private function parseNumeric($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $clean = str_replace([',', ' '], ['.', ''], trim($value));
            if (is_numeric($clean)) {
                return (float) $clean;
            }
        }
        return null;
    }

    private function extractCardLabel(string $text): string
    {
        $upper = mb_strtoupper($text);
        if (preg_match('/\\bOH\\b|OH!/', $upper)) {
            return 'OH!';
        }
        if (str_contains($upper, 'CENCOSUD')) {
            return 'Cencosud';
        }
        if (str_contains($upper, 'CMR')) {
            return 'CMR';
        }
        if (str_contains($upper, 'PRIME')) {
            return 'Prime';
        }
        return 'Tarjeta';
    }

    /**
     * @return array<int, array>
     */
    private function fetchProductsByIds(array $productIds, string $baseUrl): array
    {
        $all = [];
        $productIds = array_values(array_unique(array_filter($productIds, fn ($id) => is_string($id) && $id !== '')));
        foreach ($productIds as $id) {
            $url = $baseUrl . '/api/catalog_system/pub/products/search?fq=productId:' . rawurlencode($id);
            $requestOptions = $this->sessionOptions($url, 'plazavea_product_by_id');
            $requestOptions['headers']['Origin'] = $baseUrl;
            $requestOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
            $requestOptions['headers']['Accept'] = 'application/json,text/plain,*/*';
            $requestOptions['headers']['Referer'] = $baseUrl . '/';

            $response = $this->client->get($url, $requestOptions);
            if ($response->getStatusCode() !== 200) {
                continue;
            }

            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                continue;
            }

            foreach ($payload as $product) {
                if (is_array($product)) {
                    $all[] = $product;
                }
            }
        }

        return $all;
    }
}

