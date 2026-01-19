<?php

use GuzzleHttp\Client;

require __DIR__ . '/vendor/autoload.php';

$client = new Client([
    'timeout' => 10.0,
    'verify' => false,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]
]);

try {
    $response = $client->request('GET', 'https://tkambio.com/');
    $html = (string) $response->getBody();
    
    echo "=== Searching for API endpoints in JavaScript ===\n\n";
    
    // Look for common API patterns
    $patterns = [
        '/https?:\/\/[^"\'\s]+api[^"\'\s]*/i',
        '/fetch\(["\']([^"\']+)["\']/',
        '/axios\.[a-z]+\(["\']([^"\']+)["\']/',
        '/\$\.ajax\(\{[^}]*url:\s*["\']([^"\']+)["\']/',
        '/XMLHttpRequest[^;]+open\([^,]+,\s*["\']([^"\']+)["\']/',
    ];
    
    foreach ($patterns as $i => $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            echo "Pattern $i matches:\n";
            print_r(array_unique($matches[1] ?? $matches[0]));
            echo "\n";
        }
    }
    
    // Look for specific keywords that might indicate rate endpoints
    if (preg_match_all('/(rate|exchange|tipo|cambio)[^"\'\s]*["\']?\s*:\s*["\']([^"\']+)["\']/', $html, $matches)) {
        echo "Rate-related URLs:\n";
        print_r($matches[2]);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
