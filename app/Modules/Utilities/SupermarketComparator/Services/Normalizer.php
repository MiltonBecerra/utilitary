<?php

namespace App\Modules\Utilities\SupermarketComparator\Services;

class Normalizer
{
    private const COMBO_KEYWORDS = [
        'combo',
        'lleva',
        'gratis',
        '2da',
        'segunda',
        'tercera',
        'tarjeta',
    ];

    private const VARIANT_KEYWORDS = [
        'descremada' => 'descremada',
        'semidescremada' => 'semidescremada',
        'entera' => 'entera',
        'light' => 'light',
        'sin lactosa' => 'sin lactosa',
        'original' => 'original',
        'vainilla' => 'vainilla',
        'fresa' => 'fresa',
        'chocolate' => 'chocolate',

        // Hogar / limpieza
        'doble hoja' => 'doble hoja',
        'triple hoja' => 'triple hoja',
        'hoja simple' => 'hoja simple',
        'simple hoja' => 'hoja simple',
    ];

    private const AUDIENCE_KEYWORDS = [
        'niños' => 'niños',
        'ninos' => 'niños',
        'bebe' => 'bebé',
        'bebé' => 'bebé',
        'adulto' => 'adulto',
        'mascota' => 'mascota',
        'perro' => 'mascota',
        'gato' => 'mascota',
    ];

    public function normalizeQuery(string $query): array
    {
        $raw = trim($query);
        $lower = mb_strtolower($raw);
        $lower = preg_replace('/[\\p{P}\\p{S}]+/u', ' ', $lower) ?? $lower;
        $lower = preg_replace('/\\s+/u', ' ', $lower) ?? $lower;
        $lower = trim($lower);

        $tokens = preg_split('/\\s+/u', $lower) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));

        $hasNumber = (bool) preg_match('/\\d/', $lower);
        $hasUnit = (bool) preg_match('/\\b(kg|g|gr|l|lt|ml|cc|und|unid|unidad|pack|x)\\b/u', $lower);

        return [
            'raw' => $raw,
            'normalized' => $lower,
            'tokens' => $tokens,
            'has_number' => $hasNumber,
            'has_unit' => $hasUnit,
        ];
    }

    /**
     * Sugerencias de refinamiento conservadoras a partir del texto ingresado por el usuario.
     * No intenta adivinar marca; solo toma señales explícitas (tamaño/variante/público).
     *
     * @return array{brand:?string,size:?string,variant:?string,audience:?string,allow_similar:bool}
     */
    public function suggestRefinementFromQuery(string $query): array
    {
        $brand = null;
        try {
            $brand = (new BrandCatalog())->detectBrandInQuery($query);
        } catch (\Throwable $e) {
            $brand = null;
        }

        $presentation = $this->parsePresentation($query);

        $size = null;
        if ($presentation['total_value'] !== null && $presentation['base_unit'] !== null) {
            $size = $this->formatSizeText((float) $presentation['total_value'], (string) $presentation['base_unit']);
        }

        return [
            'brand' => $brand,
            'size' => $size,
            'variant' => $this->inferVariant($query),
            'audience' => $this->inferAudience($query),
            'allow_similar' => true,
        ];
    }

    /**
     * Intenta extraer tamaño/presentación desde texto (incluye packs).
     *
     * @return array{pack_count:int, total_value:float|null, base_unit:string|null, is_pack:bool, is_combo:bool}
     */
    public function parsePresentation(string $text): array
    {
        $t = mb_strtolower($text);
        $t = str_replace(',', '.', $t);
        $t = preg_replace('/\\s+/u', ' ', $t) ?? $t;

        $packCount = 1;
        $totalValue = null;
        $baseUnit = null;
        $isPack = false;
        $isCombo = $this->detectCombo($t);

        // pack tipo 6x330 ml
        if (preg_match('/\\b(\\d{1,3})\\s*[x×]\\s*(\\d+(?:\\.\\d+)?)\\s*(ml|l|lt|g|gr|kg)\\b/u', $t, $m)) {
            $packCount = (int) $m[1];
            $each = (float) $m[2];
            $unit = $m[3];
            [$value, $unitBase] = $this->toBaseUnit($each, $unit);
            $totalValue = $value !== null ? ($value * $packCount) : null;
            $baseUnit = $unitBase;
            $isPack = $packCount > 1;
        } elseif (preg_match('/\\b(\\d{1,3})\\s*(?:un|und|unid|unidad(?:es)?|u)\\s*[x×]\\s*(\\d+(?:\\.\\d+)?)\\s*(ml|l|lt|g|gr|kg)\\b/u', $t, $m)) {
            $packCount = (int) $m[1];
            $each = (float) $m[2];
            $unit = $m[3];
            [$value, $unitBase] = $this->toBaseUnit($each, $unit);
            $totalValue = $value !== null ? ($value * $packCount) : null;
            $baseUnit = $unitBase;
            $isPack = $packCount > 1;
        } elseif (preg_match('/\\b(?:pack\\s*)?(\\d{1,3})\\s*(?:un|und|unid|unidad(?:es)?|u)\\b/u', $t, $m)) {
            $packCount = (int) $m[1];
            $totalValue = (float) $packCount;
            $baseUnit = 'unit';
            $isPack = $packCount > 1;
        } elseif (preg_match('/\\b(\\d+(?:\\.\\d+)?)\\s*(ml|l|lt|g|gr|kg|und|unid|unidad|u)\\b/u', $t, $m)) {
            $value = (float) $m[1];
            $unit = $m[2];
            if (in_array($unit, ['und', 'unid', 'unidad', 'u'], true)) {
                $totalValue = $value;
                $baseUnit = 'unit';
            } else {
                [$valueBase, $unitBase] = $this->toBaseUnit($value, $unit);
                $totalValue = $valueBase;
                $baseUnit = $unitBase;
            }
        }

        return [
            'pack_count' => max(1, $packCount),
            'total_value' => $totalValue,
            'base_unit' => $baseUnit,
            'is_pack' => $isPack,
            'is_combo' => $isCombo && !$isPack,
        ];
    }

    /**
     * Convierte a unidad base comparable.
     *
     * @return array{0: float|null, 1: string|null} [value, unit]
     */
    public function toBaseUnit(float $value, string $unit): array
    {
        $u = mb_strtolower(trim($unit));
        $u = str_replace(['.', ' '], '', $u);

        return match ($u) {
            'kg' => [$value, 'kg'],
            'g', 'gr' => [$value / 1000, 'kg'],
            'l', 'lt' => [$value, 'l'],
            'ml', 'cc' => [$value / 1000, 'l'],
            default => [null, null],
        };
    }

    public function unitPriceText(?float $price, ?float $totalValue, ?string $baseUnit): ?string
    {
        if ($price === null || $totalValue === null || $totalValue <= 0 || $baseUnit === null) {
            return null;
        }
        $unitPrice = $price / $totalValue;
        $label = $baseUnit === 'unit' ? 'und' : $baseUnit;
        return 'S/ ' . number_format($unitPrice, 2) . ' / ' . $label;
    }

    public function signature(array $candidate): string
    {
        $title = mb_strtolower((string) ($candidate['title'] ?? ''));
        $brand = mb_strtolower((string) ($candidate['brand'] ?? ''));
        $variant = mb_strtolower((string) ($candidate['variant'] ?? ''));
        $totalValue = (string) ($candidate['total_value'] ?? '');
        $baseUnit = (string) ($candidate['base_unit'] ?? '');
        $url = mb_strtolower((string) ($candidate['url'] ?? ''));

        $key = $brand . '|' . $title . '|' . $variant . '|' . $totalValue . '|' . $baseUnit . '|' . $url;
        $key = preg_replace('/\\s+/u', ' ', $key) ?? $key;
        return trim($key);
    }

    private function detectCombo(string $normalizedText): bool
    {
        if (str_contains($normalizedText, '+')) {
            return true;
        }

        if (preg_match('/\\b\\d+\\s*\\+\\s*\\d+\\b/u', $normalizedText)) {
            return true;
        }

        if (preg_match('/\\b\\d{1,2}\\s*x\\s*\\d{1,2}\\b/u', $normalizedText)) {
            // "3x2" suele ser promo condicionada; tratar como combo.
            return true;
        }

        foreach (self::COMBO_KEYWORDS as $kw) {
            if (preg_match('/\\b' . preg_quote($kw, '/') . '\\b/u', $normalizedText)) {
                return true;
            }
        }

        return false;
    }

    public function inferVariant(string $title): ?string
    {
        $t = mb_strtolower($title);
        $t = preg_replace('/[\\p{P}\\p{S}]+/u', ' ', $t) ?? $t;
        $t = preg_replace('/\\s+/u', ' ', $t) ?? $t;
        $t = trim($t);

        // Variantes comunes en papel/tisú: 1/2/3 hojas, 2-ply/3-ply.
        if (preg_match('/\\b(\\d)\\s*(?:ply|capas?|hojas?)\\b/u', $t, $m)) {
            $n = (int) $m[1];
            if ($n === 1) {
                return 'hoja simple';
            }
            if ($n === 2) {
                return 'doble hoja';
            }
            if ($n === 3) {
                return 'triple hoja';
            }
        }

        foreach (self::VARIANT_KEYWORDS as $needle => $label) {
            if (str_contains($t, $needle)) {
                return $label;
            }
        }

        return null;
    }

    public function inferAudience(string $title): ?string
    {
        $t = mb_strtolower($title);
        $t = preg_replace('/\\s+/u', ' ', $t) ?? $t;

        foreach (self::AUDIENCE_KEYWORDS as $needle => $label) {
            if (preg_match('/\\b' . preg_quote($needle, '/') . '\\b/u', $t)) {
                return $label;
            }
        }

        return null;
    }

    public function promoHintFromTitle(string $title): ?string
    {
        $t = mb_strtolower($title);
        $t = preg_replace('/\\s+/u', ' ', $t) ?? $t;

        if (preg_match('/\\b\\d{1,2}\\s*x\\s*\\d{1,2}\\b/u', $t, $m)) {
            return 'Promo condicionada (' . str_replace(' ', '', $m[0]) . ')';
        }

        if (str_contains($t, 'con tarjeta')) {
            return 'Promo condicionada (con tarjeta)';
        }

        if (str_contains($t, '2da') || str_contains($t, 'segunda')) {
            return 'Promo condicionada (segunda unidad)';
        }

        return null;
    }

    private function formatSizeText(float $totalValue, string $baseUnit): string
    {
        $v = round($totalValue, 3);
        $v = rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');

        $u = $baseUnit === 'unit' ? 'und' : $baseUnit;
        return $v . ' ' . $u;
    }
}

