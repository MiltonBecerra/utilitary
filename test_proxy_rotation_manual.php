<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ProxyRotationService;
use GuzzleHttp\Client;

echo "--- START PROXY TEST ---\n";

try {
    $service = app(ProxyRotationService::class);
    $proxy = $service->getRandomProxy();

    if (!$proxy) {
        echo "No proxy returned. Is 'enabled' set to true in config/scraper.php?\n";
        exit(1);
    }

    echo "Using Proxy: " . $proxy . "\n";
    
    $client = new Client(['timeout' => 10]);

    // TEST 1: HTTP (Insecure)
    echo "\n--- TEST 1: HTTP (http://httpbin.org/ip) ---\n";
    try {
        $response = $client->get('http://httpbin.org/ip', ['proxy' => $proxy]);
        echo "HTTP SUCCESS! IP: " . $response->getBody() . "\n";
    } catch (\Exception $e) {
        echo "HTTP FAILED: " . $e->getMessage() . "\n";
    }

    // TEST 2: HTTPS (Secure)
    echo "\n--- TEST 2: HTTPS (https://httpbin.org/ip) ---\n";
    try {
        $response = $client->get('https://httpbin.org/ip', ['proxy' => $proxy]);
        echo "HTTPS SUCCESS! IP: " . $response->getBody() . "\n";
    } catch (\Exception $e) {
        echo "HTTPS FAILED: " . $e->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "Test Failed: " . $e->getMessage() . "\n";
}

echo "--- END PROXY TEST ---\n";
