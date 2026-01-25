<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Modules\Utilities\OfferAlerts\Services\OfferPriceScraperService;

$scraper = new OfferPriceScraperService();

// URL específica del adaptador Apple
$url = 'https://simple.ripley.com.pe/adaptador-apple-20w-2065356902093p?color_80=blanco&s=mdco';

echo "🔍 Probando scraping de Ripley para URL: {$url}\n\n";

try {
    $result = $scraper->fetchProduct($url);
    
    echo "✅ Scraping exitoso!\n";
    echo "📦 Título: " . ($result['title'] ?? 'N/A') . "\n";
    echo "🏪 Tienda: " . ($result['store'] ?? 'N/A') . "\n";
    echo "💰 Precio principal: S/ " . number_format($result['price'] ?? 0, 2) . "\n";
    echo "🌐 Precio público: S/ " . number_format($result['public_price'] ?? 0, 2) . "\n";
    echo "💳 Precio tarjeta: S/ " . number_format($result['cmr_price'] ?? 0, 2) . "\n";
    echo "🖼️  Imagen: " . ($result['image_url'] ?? 'N/A') . "\n";
    
    if (isset($result['public_price']) && isset($result['cmr_price'])) {
        $diff = $result['public_price'] - $result['cmr_price'];
        if ($diff > 0) {
            echo "💡 Ahorro con tarjeta: S/ " . number_format($diff, 2) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error en scraping: " . $e->getMessage() . "\n";
    echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🔍 Probando detección de tienda:\n";
$detectedStore = $scraper->detectStore($url);
echo "📍 Tienda detectada: {$detectedStore}\n";

echo "\n🔍 Probando normalización de URL:\n";
$reflection = new ReflectionClass($scraper);
$method = $reflection->getMethod('normalizeRipleyUrl');
$method->setAccessible(true);
$normalizedUrl = $method->invoke($scraper, $url);
echo "🔗 URL normalizada: {$normalizedUrl}\n";

echo "\n📊 Prueba completada.\n";