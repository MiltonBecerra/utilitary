<?php

namespace Tests\Unit;

use App\Modules\Utilities\SupermarketComparator\Services\Normalizer;
use PHPUnit\Framework\TestCase;

class NormalizerVariantTest extends TestCase
{
    public function testInferVariantDetectsDobleHoja(): void
    {
        $n = new Normalizer();
        $this->assertSame('doble hoja', $n->inferVariant('Papel Higiénico SUAVE Resistemax Doble Hoja Paquete 40un'));
    }

    public function testInferVariantDetectsNumericHojas(): void
    {
        $n = new Normalizer();
        $this->assertSame('doble hoja', $n->inferVariant('Papel higienico 2 hojas pack 12'));
        $this->assertSame('triple hoja', $n->inferVariant('Papel higienico 3 hojas pack 12'));
        $this->assertSame('hoja simple', $n->inferVariant('Papel higienico 1 hoja pack 12'));
    }
}



