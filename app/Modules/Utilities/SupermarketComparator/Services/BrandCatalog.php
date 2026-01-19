<?php

namespace App\Modules\Utilities\SupermarketComparator\Services;

use Illuminate\Support\Facades\File;

class BrandCatalog
{
    private const DEFAULT_MAX_BRANDS = 5000;
    private const DEFAULT_MAX_EXAMPLES = 5;

    public function __construct(
        private ?string $path = null,
        private ?string $lockPath = null,
    ) {
        $this->path = $this->path ?: storage_path('app/smc_brands.json');
        $this->lockPath = $this->lockPath ?: storage_path('app/smc_brands.lock');
    }

    /**
     * Actualiza el catálogo en vivo con candidatos ya normalizados.
     *
     * @param array<int, array{title?:string,brand?:string|null}> $candidates
     */
    public function updateFromCandidates(string $storeCode, array $candidates): void
    {
        if (!config('services.supermarket_comparator.brand_catalog_live_update', true)) {
            return;
        }

        $updates = [];
        foreach ($candidates as $c) {
            if (!is_array($c)) {
                continue;
            }
            $brand = $c['brand'] ?? null;
            if (!is_string($brand) || trim($brand) === '') {
                continue;
            }
            $title = is_string($c['title'] ?? null) ? (string) $c['title'] : '';
            $updates[] = ['brand' => $brand, 'title' => $title];
        }

        if (empty($updates)) {
            return;
        }

        $this->withLock(function (array $db) use ($storeCode, $updates) {
            foreach ($updates as $u) {
                $brandRaw = $u['brand'];
                $brandNorm = self::normalizeBrand($brandRaw);
                if ($brandNorm === null) {
                    continue;
                }

                $existing = $db['brands'][$brandNorm] ?? null;
                if (!is_array($existing)) {
                    $existing = [
                        'display' => trim($brandRaw),
                        'aliases' => [],
                        'stores' => [],
                        'total_count' => 0,
                        'examples' => [],
                        'updated_at' => null,
                    ];
                }

                $existing['total_count'] = (int) ($existing['total_count'] ?? 0) + 1;
                $existing['stores'][$storeCode] = (int) ($existing['stores'][$storeCode] ?? 0) + 1;
                $existing['updated_at'] = now()->toDateTimeString();

                // Si cambió la forma de escritura, guardarla como alias (para autocomplete/normalización).
                $display = trim($brandRaw);
                if ($display !== '' && $display !== ($existing['display'] ?? '')) {
                    $existing['aliases'][$display] = true;
                }

                $title = trim((string) ($u['title'] ?? ''));
                if ($title !== '') {
                    $examples = is_array($existing['examples'] ?? null) ? $existing['examples'] : [];
                    if (!in_array($title, $examples, true)) {
                        $examples[] = $title;
                        $existing['examples'] = array_slice($examples, 0, (int) config('services.supermarket_comparator.brand_catalog_max_examples', self::DEFAULT_MAX_EXAMPLES));
                    }
                }

                $db['brands'][$brandNorm] = $existing;
            }

            $db['updated_at'] = now()->toDateTimeString();
            $db['version'] = 1;

            $max = (int) config('services.supermarket_comparator.brand_catalog_max', self::DEFAULT_MAX_BRANDS);
            $db = $this->trimToMax($db, $max);

            return $db;
        });
    }

    /**
     * Detecta marca en el texto del usuario usando el catálogo existente (conservador).
     */
    public function detectBrandInQuery(string $query): ?string
    {
        $queryNorm = self::normalizeText($query);
        if ($queryNorm === '') {
            return null;
        }

        $db = $this->load();
        $brands = is_array($db['brands'] ?? null) ? $db['brands'] : [];
        if (empty($brands)) {
            return null;
        }

        // Heurística 0 (más eficiente): buscar coincidencias exactas por n-grams de tokens (1..5 palabras).
        // Esto permite detectar marcas recién agregadas por el usuario sin recorrer todo el catálogo.
        $tokens = preg_split('/\s+/u', $queryNorm) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));
        $maxWords = min(5, count($tokens));
        if ($maxWords > 0) {
            for ($start = 0; $start < count($tokens); $start++) {
                for ($len = 1; $len <= $maxWords; $len++) {
                    if ($start + $len > count($tokens)) {
                        break;
                    }
                    $ngram = implode(' ', array_slice($tokens, $start, $len));
                    if (isset($brands[$ngram]) && is_array($brands[$ngram])) {
                        $display = $brands[$ngram]['display'] ?? null;
                        return is_string($display) && trim($display) !== '' ? trim($display) : null;
                    }
                }
            }
        }

        // Heurística: intentar match por tokens (marca en cualquier parte), priorizando marcas más frecuentes.
        // Para performance, limitar a marcas más frecuentes.
        $top = $this->topBrands($brands, (int) config('services.supermarket_comparator.brand_catalog_detect_limit', 800));
        foreach ($top as $brandNorm => $row) {
            if (!is_array($row)) {
                continue;
            }
            if (self::containsTokenBoundary($queryNorm, (string) $brandNorm)) {
                $display = $row['display'] ?? null;
                return is_string($display) && trim($display) !== '' ? trim($display) : null;
            }
        }

        return null;
    }

    public function load(): array
    {
        try {
            if (!File::exists($this->path)) {
                return ['version' => 1, 'updated_at' => null, 'brands' => []];
            }
            $raw = File::get($this->path);
            $data = json_decode((string) $raw, true);
            if (!is_array($data)) {
                return ['version' => 1, 'updated_at' => null, 'brands' => []];
            }
            if (!is_array($data['brands'] ?? null)) {
                $data['brands'] = [];
            }
            return $data;
        } catch (\Throwable $e) {
            return ['version' => 1, 'updated_at' => null, 'brands' => []];
        }
    }

    /**
     * Devuelve sugerencias de marcas para autocompletar.
     *
     * @return string[] display names
     */
    public function suggest(string $query, int $limit = 12): array
    {
        $qNorm = self::normalizeBrand($query);
        if ($qNorm === null || mb_strlen($qNorm) < 2) {
            return [];
        }

        $db = $this->load();
        $brands = is_array($db['brands'] ?? null) ? $db['brands'] : [];
        if (empty($brands)) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        $matches = [];
        foreach ($brands as $brandNorm => $row) {
            if (!is_string($brandNorm) || !is_array($row)) {
                continue;
            }

            $display = $row['display'] ?? null;
            $display = is_string($display) ? trim($display) : '';
            if ($display === '') {
                continue;
            }

            $bn = (string) $brandNorm;
            $score = null;
            if (str_starts_with($bn, $qNorm)) {
                $score = 300;
            } elseif (str_contains($bn, $qNorm)) {
                $score = 200;
            } else {
                // match por display/alias (normalizados)
                $dn = self::normalizeBrand($display) ?? '';
                if ($dn !== '' && str_contains($dn, $qNorm)) {
                    $score = 150;
                } else {
                    $aliases = $row['aliases'] ?? null;
                    if (is_array($aliases)) {
                        foreach (array_keys($aliases) as $a) {
                            if (!is_string($a)) {
                                continue;
                            }
                            $an = self::normalizeBrand($a) ?? '';
                            if ($an !== '' && str_contains($an, $qNorm)) {
                                $score = 120;
                                break;
                            }
                        }
                    }
                }
            }

            if ($score === null) {
                continue;
            }

            $freq = (int) ($row['total_count'] ?? 0);
            $matches[$display] = max($matches[$display] ?? 0, $score + min(50, (int) floor(log(max(1, $freq), 2))));
        }

        if (empty($matches)) {
            return [];
        }

        arsort($matches);
        return array_slice(array_keys($matches), 0, $limit);
    }

    private function withLock(callable $fn): void
    {
        File::ensureDirectoryExists(dirname($this->path));

        $fp = @fopen($this->lockPath, 'c+');
        if (!$fp) {
            // Best-effort: sin lock.
            $db = $this->load();
            $next = $fn($db);
            $this->save($next);
            return;
        }

        try {
            if (@flock($fp, LOCK_EX)) {
                $db = $this->load();
                $next = $fn($db);
                $this->save($next);
                @flock($fp, LOCK_UN);
            }
        } finally {
            @fclose($fp);
        }
    }

    private function save(array $db): void
    {
        try {
            $tmp = $this->path . '.tmp';
            File::put($tmp, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            @rename($tmp, $this->path);
        } catch (\Throwable $e) {
            // Best-effort
        }
    }

    private function trimToMax(array $db, int $max): array
    {
        $brands = is_array($db['brands'] ?? null) ? $db['brands'] : [];
        if ($max <= 0 || count($brands) <= $max) {
            $db['brands'] = $brands;
            return $db;
        }

        uasort($brands, function ($a, $b) {
            $ac = is_array($a) ? (int) ($a['total_count'] ?? 0) : 0;
            $bc = is_array($b) ? (int) ($b['total_count'] ?? 0) : 0;
            return $bc <=> $ac;
        });

        $db['brands'] = array_slice($brands, 0, $max, true);
        return $db;
    }

    /**
     * @param array<string, array> $brands
     * @return array<string, array>
     */
    private function topBrands(array $brands, int $limit): array
    {
        if ($limit <= 0 || count($brands) <= $limit) {
            return $brands;
        }

        uasort($brands, function ($a, $b) {
            $ac = is_array($a) ? (int) ($a['total_count'] ?? 0) : 0;
            $bc = is_array($b) ? (int) ($b['total_count'] ?? 0) : 0;
            return $bc <=> $ac;
        });

        return array_slice($brands, 0, $limit, true);
    }

    public static function normalizeBrand(?string $brand): ?string
    {
        if ($brand === null) {
            return null;
        }
        $b = trim($brand);
        if ($b === '') {
            return null;
        }

        $b = mb_strtolower($b);
        $b = preg_replace('/[\\p{P}\\p{S}]+/u', ' ', $b) ?? $b;
        $b = preg_replace('/\\s+/u', ' ', $b) ?? $b;
        $b = trim($b);
        return $b === '' ? null : $b;
    }

    private static function normalizeText(string $text): string
    {
        $t = trim(mb_strtolower($text));
        $t = preg_replace('/[\\p{P}\\p{S}]+/u', ' ', $t) ?? $t;
        $t = preg_replace('/\\s+/u', ' ', $t) ?? $t;
        return trim($t);
    }

    private static function startsWithTokenBoundary(string $haystack, string $needle): bool
    {
        $needle = trim($needle);
        if ($needle === '') {
            return false;
        }
        if (!str_starts_with($haystack, $needle)) {
            return false;
        }
        if (mb_strlen($haystack) === mb_strlen($needle)) {
            return true;
        }
        $next = mb_substr($haystack, mb_strlen($needle), 1);
        return $next === ' ';
    }

    private static function containsTokenBoundary(string $haystack, string $needle): bool
    {
        $needle = trim($needle);
        if ($needle === '') {
            return false;
        }

        $pos = 0;
        while (true) {
            $idx = mb_strpos($haystack, $needle, $pos);
            if ($idx === false) {
                return false;
            }

            $beforeOk = $idx === 0 || mb_substr($haystack, $idx - 1, 1) === ' ';
            $afterPos = $idx + mb_strlen($needle);
            $afterOk = $afterPos >= mb_strlen($haystack) || mb_substr($haystack, $afterPos, 1) === ' ';

            if ($beforeOk && $afterOk) {
                return true;
            }

            $pos = $idx + 1;
        }
    }
}

