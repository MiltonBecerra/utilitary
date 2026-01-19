<?php

namespace Tests\Unit;

use App\Modules\Utilities\SupermarketComparator\Services\EquivalenceService;
use PHPUnit\Framework\TestCase;

class EquivalenceServiceBrandNormalizationTest extends TestCase
{
    public function testBrandMatchesIgnoringApostrophes(): void
    {
        $svc = new EquivalenceService();

        $refinement = [
            'brand' => "BUCHANAN'S",
            'variant' => null,
            'audience' => null,
            'size' => null,
        ];

        $candidate = [
            'title' => "Whisky Buchanan's Deluxe 12 Años Botella 1L",
            'brand' => "Buchanan’s",
            'variant' => null,
            'audience' => null,
            'total_value' => null,
            'base_unit' => null,
        ];

        $out = $svc->classify($refinement, $candidate);
        $this->assertNotSame('none', $out['level']);
    }

    public function testBrandFallsBackToTitleWhenBrandMissing(): void
    {
        $svc = new EquivalenceService();

        $refinement = [
            'brand' => "BUCHANAN'S",
            'variant' => null,
            'audience' => null,
            'size' => null,
        ];

        $candidate = [
            'title' => "Whisky Buchanan's Deluxe 12 Años Botella 1L",
            'brand' => null,
            'variant' => null,
            'audience' => null,
            'total_value' => null,
            'base_unit' => null,
        ];

        $out = $svc->classify($refinement, $candidate);
        $this->assertNotSame('none', $out['level']);
    }
}



