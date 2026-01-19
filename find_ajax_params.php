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
    // Get the main page HTML
    $response = $client->request('GET', 'https://tkambio.com/');
    $html = (string) $response->getBody();
    
    echo "=== Searching for AJAX calls in JavaScript ===\n\n";
    
    // Look for admin-ajax.php calls
    if (preg_match_all('/admin-ajax\.php[^;]+/i', $html, $matches)) {
        echo "Found admin-ajax.php references:\n";
        foreach ($matches[0] as $match) {
            echo "  " . substr($match, 0, 200) . "\n";
        }
        echo "\n";
    }
    
    // Look for jQuery.ajax or $.ajax calls
    if (preg_match_all('/\$\.ajax\(\{[^}]+\}/i', $html, $matches)) {
        echo "Found $.ajax calls:\n";
        foreach ($matches[0] as $match) {
            echo "  " . $match . "\n";
        }
        echo "\n";
    }
    
    // Look for action parameters
    if (preg_match_all('/action["\']?\s*:\s*["\']([^"\']+)["\']/i', $html, $matches)) {
        echo "Found action parameters:\n";
        print_r(array_unique($matches[1]));
        echo "\n";
    }
    
    // Save a portion of HTML containing scripts
    if (preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $scripts)) {
        echo "Found " . count($scripts[1]) . " script tags\n";
        
        // Look for scripts that mention admin-ajax
        foreach ($scripts[1] as $i => $script) {
            if (stripos($script, 'admin-ajax') !== false) {
                echo "\nScript #$i contains admin-ajax:\n";
                echo substr($script, 0, 1000) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
