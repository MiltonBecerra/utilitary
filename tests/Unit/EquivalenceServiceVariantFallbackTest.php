<?php

namespace Tests\Unit;

use App\Modules\Utilities\SupermarketComparator\Services\EquivalenceService;
use PHPUnit\Framework\TestCase;

class EquivalenceServiceVariantFallbackTest extends TestCase
{
    public function testVariantMatchesAgainstTitleWhenCandidateVariantMissing(): void
    {
        $svc = new EquivalenceService();

        $refinement = [
            'brand' => null,
            'variant' => 'azul',
            'audience' => null,
            'size' => null,
        ];

        $candidate = [
            'title' => 'Artesco Lapicero Tinta Seca CR-31 Azul Blíster 5 unid Pack x5',
            'brand' => null,
            'variant' => null,
            'audience' => null,
            'total_value' => null,
            'base_unit' => null,
        ];

        $out = $svc->classify($refinement, $candidate);
        $this->assertNotSame('none', $out['level']);
    }

    public function testVariantMismatchWhenCandidateVariantExplicit(): void
    {
        $svc = new EquivalenceService();

        $refinement = [
            'brand' => null,
            'variant' => 'azul',
            'audience' => null,
            'size' => null,
        ];

        $candidate = [
            'title' => 'Lapicero rojo',
            'brand' => null,
            'variant' => 'rojo',
            'audience' => null,
            'total_value' => null,
            'base_unit' => null,
        ];

        $out = $svc->classify($refinement, $candidate);
        $this->assertNotSame('identical', $out['level']);
    }
}


