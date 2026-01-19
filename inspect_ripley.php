<?php

use App\Services\OfferPriceScraperService;
use GuzzleHttp\Client;

require __DIR__ . '/vendor/autoload.php';

// Bootstrap simple Laravel-like environment if needed, or just use classes if decoupling is clean.
// Since OfferPriceScraperService depends on config() helpers, we might need to mock or load app.
// For simplicity in this existing Laravel project, we can boot the app.

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$url = $argv[1] ?? 'https://simple.ripley.com.pe/zapatillas-mujer-nike-tenis-negro-vapor-lite-3-hc-2084356108205?color_80=negro&s=mdco';

echo "Inspecting URL: $url\n";

try {
    $service = new OfferPriceScraperService();
    $result = $service->fetchProduct($url);

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
