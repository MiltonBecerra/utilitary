<?php

use GuzzleHttp\Client;

require __DIR__ . '/vendor/autoload.php';

$client = new Client([
    'timeout' => 10.0,
    'verify' => false,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
        'Accept' => 'application/json, text/javascript, */*; q=0.01',
        'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With' => 'XMLHttpRequest',
        'Origin' => 'https://tkambio.com',
        'Referer' => 'https://tkambio.com/',
    ]
]);

$url = 'https://tkambio.com/wp-admin/admin-ajax.php';

echo "=== Testing Tkambio AJAX with POST ===\n\n";

// Try different action parameters as POST
$actions = [
    ['action' => 'get_exchange_rate'],
    ['action' => 'get_rates'],
    ['action' => 'exchange_rate'],
    ['action' => 'tkambio_rates'],
    ['action' => 'get_tipo_cambio'],
    ['action' => 'calculator'],
    ['action' => 'get_calculator_data'],
];

foreach ($actions as $params) {
    try {
        $actionName = $params['action'];
        echo "Testing POST with action: '$actionName'\n";
        
        $response = $client->request('POST', $url, [
            'form_params' => $params
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
        
        if ($statusCode == 200 && $body != '0' && $body != '-1') {
            echo "  ✓ SUCCESS! This action works!\n\n";
            break;
        }
        
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}
