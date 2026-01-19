<?php
require __DIR__.'/vendor/autoload.php';
$client = new GuzzleHttp\Client([
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept' => 'application/json, text/plain, */*',
        'Referer' => 'https://kambista.com/',
        'Origin' => 'https://kambista.com',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept-Language' => 'es-PE,es;q=0.9,en;q=0.8',
    ],
    'http_errors' => false,
]);
$res = $client->get('https://api.kambista.com/v1/exchange/tcs/v2');
echo $res->getStatusCode(), "\n";
echo substr($res->getBody()->getContents(),0,500);
?>
