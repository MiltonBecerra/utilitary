<?php

namespace App\Modules\Utilities\SupermarketComparator\Services;

class AmbiguityDetector
{
    private const STRONG_MATCH_THRESHOLD = 85.0;

    /**
     * @return array{is_ambiguous: bool, reasons: string[]}
     */
    public function analyze(string $query, array $querySignals = [], array $candidates = []): array
    {
        $q = trim(mb_strtolower($query));

        if ($this->hasStrongTitleMatch($q, $candidates)) {
            return [
                'is_ambiguous' => false,
                'reasons' => [],
            ];
        }

        $tokens = preg_split('/\s+/', $q) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));

        $reasons = [];

        if (count($tokens) <= 2) {
            $reasons[] = 'Búsqueda muy corta; agrega marca y/o tamaño.';
        }

        $generic = [
            'leche', 'arroz', 'aceite', 'azucar', 'azúcar', 'sal',
            'pollo', 'carne', 'atun', 'atún', 'fideos',
            'detergente', 'shampoo', 'jabón', 'jabon',
            'papel', 'pañales', 'panales', 'toallitas',
        ];

        if ($this->genericTermsEnabled()) {
            foreach ($generic as $term) {
                if (preg_match('/\b' . preg_quote($term, '/') . '\b/u', $q)) {
                    $reasons[] = 'Término genérico detectado; define variante, marca y presentación.';
                    break;
                }
            }
        }

        if (!($querySignals['has_number'] ?? false) && !($querySignals['has_unit'] ?? false)) {
            $reasons[] = 'No se detectó tamaño/presentación (ej: 1 L, 900 g, 12 und, 6x330 ml).';
        }

        $isAmbiguous = count($reasons) > 0;

        return [
            'is_ambiguous' => $isAmbiguous,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function hasStrongTitleMatch(string $query, array $candidates): bool
    {
        if ($query === '' || empty($candidates)) {
            return false;
        }

        $queryNorm = $this->normalizeText($query);
        if ($queryNorm === '') {
            return false;
        }

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $title = (string) ($candidate['title'] ?? $candidate['name'] ?? '');
            $titleNorm = $this->normalizeText($title);
            if ($titleNorm === '') {
                continue;
            }

            if ($queryNorm === $titleNorm) {
                return true;
            }

            $percent = 0.0;
            similar_text($queryNorm, $titleNorm, $percent);
            if ($percent >= self::STRONG_MATCH_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = preg_replace('/[\p{P}\p{S}]+/u', ' ', $t) ?? $t;
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
        return trim($t);
    }

    private function genericTermsEnabled(): bool
    {
        $flag = config('services.supermarket_comparator.enable_generic_terms', false);
        return (bool) $flag;
    }
}
