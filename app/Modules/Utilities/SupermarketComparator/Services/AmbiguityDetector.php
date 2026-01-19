<?php

namespace App\Modules\Utilities\SupermarketComparator\Services;

class AmbiguityDetector
{
    /**
     * @return array{is_ambiguous: bool, reasons: string[]}
     */
    public function analyze(string $query, array $querySignals = []): array
    {
        $q = trim(mb_strtolower($query));
        $tokens = preg_split('/\\s+/', $q) ?: [];
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

        foreach ($generic as $term) {
            if (preg_match('/\\b' . preg_quote($term, '/') . '\\b/u', $q)) {
                $reasons[] = 'Término genérico detectado; define variante, marca y presentación.';
                break;
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
}


