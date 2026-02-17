<?php

namespace App\Modules\Utilities\SupermarketComparator\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EquivalenceService
{
    private const STRONG_MATCH_THRESHOLD = 85.0;

    /**
     * @return array{level: 'identical'|'similar'|'none', explain: string}
     */
    public function classify(array $refinement, array $candidate): array
    {
        if ($this->isStrongTitleMatch($refinement['_query'] ?? null, (string) ($candidate['title'] ?? ''))) {
            return ['level' => 'identical', 'explain' => 'Coincide por nombre'];
        }

        $brandWanted = $this->norm($refinement['brand'] ?? null);
        $variantWanted = $this->norm($refinement['variant'] ?? null);
        $audienceWanted = $this->norm($refinement['audience'] ?? null);

        $brand = $this->norm($candidate['brand'] ?? null);
        $variant = $this->norm($candidate['variant'] ?? null);
        $audience = $this->norm($candidate['audience'] ?? null);
        $titleNorm = $this->normalizeText((string) ($candidate['title'] ?? ''));

        $explain = [];

        $brandMatches = null;
        if ($brandWanted) {
            if ($brand !== null) {
                $brandMatches = ($brandWanted === $brand);
            } else {
                // Fallback: algunas tiendas no exponen brand; buscar en el título para no filtrar de más.
                $brandWantedInTitle = $this->normalizeText($brandWanted);
                $brandMatches = $brandWantedInTitle !== '' && self::containsTokenBoundary($titleNorm, $brandWantedInTitle);
            }
        }
        if ($brandMatches === false) {
            $explain[] = 'Marca distinta';
        }

        // Variante: match exacto si el candidato la expone; si no, fallback a buscar el texto en el título
        // (ej: usuario pone color "azul" y el scraper no llenó `variant`).
        $variantMatches = null;
        if ($variantWanted) {
            if ($variant !== null) {
                $variantMatches = ($variantWanted === $variant);
            } else {
                $variantMatches = self::containsTokenBoundary($titleNorm, $variantWanted);
            }
        }
        if ($variantMatches === false) {
            $explain[] = 'Variante distinta';
        }

        $audienceMatches = $audienceWanted ? ($audienceWanted === $audience) : null;
        if ($audienceMatches === false) {
            return ['level' => 'none', 'explain' => 'Público objetivo distinto'];
        }

        // Unidad base: si difiere (kg vs l vs unidad), no es comparable.
        $candidateUnit = $candidate['base_unit'] ?? null;

        // Tamaño/presentación: compara por unidad base cuando exista.
        $baseWanted = $this->parseSizeWanted($refinement['size'] ?? null);
        $sizeMatchesIdentical = null;
        $sizeMatchesSimilar = null;
        if ($baseWanted['total_value'] !== null && $baseWanted['base_unit'] !== null) {
            $total = $candidate['total_value'] ?? null;
            $unit = $candidate['base_unit'] ?? null;
            if ($unit !== null && $unit !== $baseWanted['base_unit']) {
                return ['level' => 'none', 'explain' => 'Unidad base distinta'];
            }
            if ($total !== null && $unit === $baseWanted['base_unit']) {
                $wanted = (float) $baseWanted['total_value'];

                // Si el candidato es pack (ej: 6x60ml), permitir refinamiento por tamaño por unidad (60ml)
                // además del tamaño total (360ml).
                $possibleTotals = [(float) $total];
                $candidatePackCount = (int) ($candidate['pack_count'] ?? 1);
                $candidateIsPack = (bool) ($candidate['is_pack'] ?? false);
                if ($candidateIsPack && $candidatePackCount > 1) {
                    $possibleTotals[] = (float) $total / $candidatePackCount;
                }

                $bestDiff = null;
                foreach ($possibleTotals as $candTotal) {
                    $d = abs($candTotal - $wanted);
                    if ($bestDiff === null || $d < $bestDiff) {
                        $bestDiff = $d;
                    }
                }
                $diff = $bestDiff ?? abs(((float) $total) - $wanted);

                $toleranceIdentical = max(0.02 * $wanted, 0.001);
                $toleranceSimilar = max(0.10 * $wanted, 0.001);
                $sizeMatchesIdentical = $diff <= $toleranceIdentical;
                $sizeMatchesSimilar = $diff <= $toleranceSimilar;
                if (!$sizeMatchesSimilar) {
                    return ['level' => 'none', 'explain' => 'Presentación muy distinta'];
                }
                if (!$sizeMatchesIdentical) {
                    $explain[] = 'Presentación distinta';
                }
            } else {
                // El usuario fijó tamaño, pero el candidato no tiene medida interpretable: no comparar.
                return ['level' => 'none', 'explain' => 'Sin presentación comparable'];
            }
        } elseif ($candidateUnit !== null && !in_array($candidateUnit, ['kg', 'l', 'unit'], true)) {
            return ['level' => 'none', 'explain' => 'Unidad no comparable'];
        }

        $hasSomeRefinement = (bool) ($brandWanted || $variantWanted || $audienceWanted || ($baseWanted['total_value'] !== null));

        if (!$hasSomeRefinement) {
            if ($this->queryMatchesTitle($refinement['_query'] ?? null, (string) ($candidate['title'] ?? ''))) {
                return ['level' => 'identical', 'explain' => 'Coincide por nombre'];
            }
            return ['level' => 'similar', 'explain' => 'Falta refinamiento para asegurar identidad'];
        }

        // Evita falsos "idénticos" cuando solo se conoce el tamaño/presentación (ej: muchos productos de 1 kg).
        // Si el refinamiento solo aporta tamaño, se considera "similar" hasta que el usuario agregue marca/variante/público.
        $hasOnlySizeRefinement = ($baseWanted['total_value'] !== null) && !$brandWanted && !$variantWanted && !$audienceWanted;
        if ($hasOnlySizeRefinement) {
            return ['level' => 'similar', 'explain' => 'Solo tamaño no basta para asegurar identidad'];
        }

        // Regla de identidad: marca+variante deben coincidir si fueron especificadas, y el tamaño debe estar dentro de tolerancia 2%.
        $isIdentical = true;
        if ($brandMatches === false || $variantMatches === false) {
            $isIdentical = false;
        }
        if ($sizeMatchesIdentical === false) {
            $isIdentical = false;
        }

        if ($isIdentical) {
            return ['level' => 'identical', 'explain' => 'Coincide con el refinamiento'];
        }

        return ['level' => 'similar', 'explain' => implode(', ', array_unique($explain)) ?: 'Similar'];
    }

    private function norm(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = trim(mb_strtolower($value));
        $v = $this->normalizeAccents($v);

        // Normalizar apóstrofes típicos para evitar mismatches: Buchanan's vs Buchanans vs Buchanan’s vs Buchanan´s
        $v = str_replace(["'", '’', '´', '`'], '', $v);

        // Quitar puntuación/símbolos y normalizar espacios
        $v = preg_replace('/[\\p{P}\\p{S}]+/u', ' ', $v) ?? $v;
        $v = preg_replace('/\\s+/u', ' ', $v) ?? $v;
        return $v === '' ? null : $v;
    }

    private function normalizeText(string $text): string
    {
        $t = trim(mb_strtolower($text));
        $t = $this->normalizeAccents($t);
        $t = preg_replace('/[\\p{P}\\p{S}]+/u', ' ', $t) ?? $t;
        $t = preg_replace('/\\s+/u', ' ', $t) ?? $t;
        return trim($t);
    }

    private function normalizeAccents(string $value): string
    {
        return Str::ascii($value);
    }

    private function queryMatchesTitle(?string $query, string $title): bool
    {
        if ($query === null || trim($query) === '') {
            return false;
        }

        $q = $this->normalizeText($query);
        if ($q === '') {
            return false;
        }

        $titleNorm = $this->normalizeText($title);
        if ($titleNorm === '') {
            return false;
        }

        $tokens = preg_split('/\\s+/u', $q) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));
        if (count($tokens) === 0 || count($tokens) > 2) {
            return false;
        }

        $first = $tokens[0];
        $startsWith = $this->startsWithToken($titleNorm, $first);
        if (config('app.debug') || env('SMC_DEBUG_MATCH_LOG')) {
            Log::info('smc_match_check', [
                'query' => $query,
                'title' => $title,
                'first_token' => $first,
                'starts_with' => $startsWith,
            ]);
        }
        if (!$startsWith) {
            return false;
        }

        $matches = (int) similar_text($q, $titleNorm);
        $qlen = max(1, mb_strlen($q));
        $ratio = ($matches / $qlen) * 100;

        if (config('app.debug') || env('SMC_DEBUG_MATCH_LOG')) {
            Log::info('smc_match_ratio', [
                'query' => $query,
                'title' => $title,
                'matches' => $matches,
                'query_len' => $qlen,
                'ratio' => $ratio,
            ]);
        }

        return $ratio >= 50;
    }

    private function isStrongTitleMatch(?string $query, string $title): bool
    {
        if ($query === null || trim($query) === '') {
            return false;
        }

        $q = $this->normalizeText($query);
        if ($q === '') {
            return false;
        }

        $titleNorm = $this->normalizeText($title);
        if ($titleNorm === '') {
            return false;
        }

        if ($q === $titleNorm) {
            return true;
        }

        $percent = 0.0;
        similar_text($q, $titleNorm, $percent);
        return $percent >= self::STRONG_MATCH_THRESHOLD;
    }

    private function startsWithToken(string $text, string $token): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        if (mb_strpos($text, $token) !== 0) {
            return false;
        }

        $afterPos = mb_strlen($token);
        return $afterPos >= mb_strlen($text) || mb_substr($text, $afterPos, 1) === ' ';
    }

    private static function containsTokenBoundary(string $text, string $token): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        $pattern = '/(^|\\s)' . preg_quote($token, '/') . '(\\s|$)/u';
        return preg_match($pattern, $text) === 1;
    }

    /**
     * @return array{total_value: float|null, base_unit: string|null, pack_count: int, is_pack: bool}
     */
    private function parseSizeWanted(?string $size): array
    {
        if ($size === null || trim($size) === '') {
            return ['total_value' => null, 'base_unit' => null, 'pack_count' => 1, 'is_pack' => false];
        }
        $n = new Normalizer();
        $parsed = $n->parsePresentation($size);
        return [
            'total_value' => $parsed['total_value'],
            'base_unit' => $parsed['base_unit'],
            'pack_count' => (int) ($parsed['pack_count'] ?? 1),
            'is_pack' => (bool) ($parsed['is_pack'] ?? false),
        ];
    }
}

