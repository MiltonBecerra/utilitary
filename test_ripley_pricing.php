<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Modules\Utilities\OfferAlerts\Services\OfferPriceScraperService;

$scraper = new OfferPriceScraperService();

$urlRefrigerador = 'https://simple.ripley.com.pe/refrigerador-side-by-side-hisense-529-l-rs3p558esa-sd-gris-pmp20001280440?cat=refrigeradoras&pos=3&p=1&ps=48&s=mdco';

echo "=== Prueba con Refrigerador Hisense (URL con solo un precio) ===\n";
echo "URL: {$urlRefrigerador}\n\n";

try {
    $result = $scraper->fetchProduct($urlRefrigerador);
    
    echo "Resultados:\n";
    echo "Title: " . $result['title'] . "\n";
    echo "Price: S/. " . number_format($result['price'], 2) . "\n";
    echo "Public Price: S/. " . number_format($result['public_price'], 2) . "\n";
    echo "CMR Price: S/. " . number_format($result['cmr_price'], 2) . "\n";
    echo "Store: " . $result['store'] . "\n";
    
    // Validar el comportamiento esperado
    echo "\n=== Validación ===\n";
    
    if ($result['public_price'] == $result['cmr_price']) {
        echo "✅ Correcto: cmr_price es igual a public_price\n";
    } else {
        echo "❌ Error: cmr_price debería ser igual a public_price\n";
    }
    
    if ($result['price'] == $result['public_price'] && $result['price'] == $result['cmr_price']) {
        echo "✅ Correcto: Los 3 precios son iguales (escenario 1)\n";
    } else {
        echo "❌ Error: Los 3 precios deberían ser iguales\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";

// Prueba adicional con URL de adaptador Apple (con 2 precios)
$urlAdaptador = 'https://simple.ripley.com.pe/adaptador-apple-20w-2065356902093p?color_80=blanco&s=mdco';

echo "=== Prueba con Adaptador Apple (URL con 2 precios) ===\n";
echo "URL: {$urlAdaptador}\n\n";

try {
    $result = $scraper->fetchProduct($urlAdaptador);
    
    echo "Resultados:\n";
    echo "Title: " . $result['title'] . "\n";
    echo "Price: S/. " . number_format($result['price'], 2) . "\n";
    echo "Public Price: S/. " . number_format($result['public_price'], 2) . "\n";
    echo "CMR Price: S/. " . number_format($result['cmr_price'], 2) . "\n";
    echo "Store: " . $result['store'] . "\n";
    
    echo "\n=== Validación ===\n";
    
    if ($result['public_price'] > $result['cmr_price']) {
        echo "✅ Correcto: public_price > cmr_price (descuento tarjeta)\n";
    } else {
        echo "❌ Error: public_price debería ser mayor que cmr_price\n";
    }
    
    if ($result['price'] == $result['cmr_price']) {
        echo "✅ Correcto: price usa el mejor precio (cmr_price)\n";
    } else {
        echo "❌ Error: price debería usar el mejor precio\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}