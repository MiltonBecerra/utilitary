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

$url = 'https://tkambio.com/wp-admin/admin-ajax.php';

echo "=== Testing Tkambio AJAX Endpoint ===\n\n";

// Try different action parameters
$actions = [
    'get_exchange_rate',
    'get_rates',
    'exchange_rate',
    'tkambio_rates',
    'get_tipo_cambio',
    '', // Empty action
];

foreach ($actions as $action) {
    try {
        echo "Testing with action: '$action'\n";
        
        $params = $action ? ['action' => $action] : [];
        
        $response = $client->request('GET', $url, [
            'query' => $params
        ]);
        
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo "  Status: $statusCode\n";
        echo "  Body: " . substr($body, 0, 500) . "\n";
        
        // Try to decode as JSON
        $json = json_decode($body, true);
        if ($json) {
            echo "  JSON decoded:\n";
            print_r($json);
        }
        echo "\n";
        
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}

// Also try POST
echo "\n=== Testing POST request ===\n";
try {
    $response = $client->request('POST', $url);
    $body = (string) $response->getBody();
    echo "Body: " . substr($body, 0, 500) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
