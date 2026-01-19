<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

$proxies = [
    "http://1.0.171.213:8080", "http://1.0.205.87:8080", "http://1.1.189.58:8080", "http://1.1.220.63:8080",
    "http://1.9.83.210:1337", "http://1.10.141.115:8080", "http://1.20.207.99:8080", "http://1.20.225.123:8080",
    "http://1.85.52.250:9797", "http://1.179.144.41:8080", "http://1.179.148.9:55636", "http://2.58.217.1:8080",
    "http://2.138.28.204:3128", "http://3.20.236.208:49205", "http://3.94.253.49:8118", "http://3.215.177.148:49205",
    "http://4.16.68.158:443", "http://5.8.53.7:18081", "http://5.9.180.204:8080", "http://5.54.186.76:8080",
    "http://5.75.144.136:8080", "http://5.78.40.148:8080", "http://5.78.42.62:50001", "http://5.78.42.170:8080",
    "http://5.161.110.95:50001", "http://8.219.176.202:8080", "http://14.241.225.167:443", "http://20.74.169.104:8118",
    "http://34.118.65.91:3128", "http://43.156.100.152:80"
];

$client = new Client([
    'timeout' => 3, // Fast fail
    'connect_timeout' => 2,
]);

echo "Scanning " . count($proxies) . " proxies...\n";

foreach ($proxies as $proxy) {
    echo "Testing $proxy ... ";
    try {
        $response = $client->get('https://httpbin.org/ip', [
            'proxy' => $proxy
        ]);
        
        if ($response->getStatusCode() === 200) {
            echo "SUCCESS!\n";
            echo "Working Proxy Found: " . $proxy . "\n";
            file_put_contents('working_proxy.txt', $proxy);
            exit(0);
        }
    } catch (\Throwable $e) {
        echo "Failed.\n";
    }
}

echo "No working proxy found in this batch.\n";
exit(1);
