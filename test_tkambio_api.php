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

$endpoints = [
    'https://tkambio.com/api/rates',
    'https://tkambio.com/api/exchange-rates',
    'https://tkambio.com/api/v1/rates',
    'https://api.tkambio.com/rates',
    'https://api.tkambio.com/v1/rates',
    'https://tkambio.com/wp-json/tkambio/v1/rates',
    'https://tkambio.com/rates',
    'https://tkambio.com/exchange-rate',
];

echo "=== Testing Tkambio API Endpoints ===\n\n";

foreach ($endpoints as $endpoint) {
    try {
        echo "Testing: $endpoint\n";
        $response = $client->request('GET', $endpoint);
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo "  Status: $statusCode\n";
        if ($statusCode == 200) {
            echo "  Body (first 500 chars): " . substr($body, 0, 500) . "\n";
            
            // Try to decode as JSON
            $json = json_decode($body, true);
            if ($json) {
                echo "  JSON decoded successfully!\n";
                print_r($json);
            }
        }
        echo "\n";
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo "  Error: " . $e->getCode() . " - " . $e->getMessage() . "\n\n";
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}
