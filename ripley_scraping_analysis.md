# Análisis y Soluciones para Scraping de Precios Ripley

## Problemas Detectados

### 1. Error 403 Forbidden - Bloqueo Anti-Bot
Ripley está bloqueando activamente las solicitudes del scraper con error 403 Forbidden, impidiendo cualquier extracción de precios.

**Evidencia en logs:**
```
offer_scrape_response {"url":"https://www.ripley.com.pe/...","status":403}
```

### 2. Falta de Servidor Puppeteer
El sistema intenta usar Puppeteer como fallback pero no hay servidor corriendo en localhost:3001.

**Error en logs:**
```
puppeteer_api_exception {"error":"cURL error 7: Failed to connect to localhost port 3001"}
```

### 3. Selectores CSS Incompletos
Los selectores actuales no cubren todos los casos de la estructura HTML de Ripley.

### 4. Validación Ineficiente de Precio de Tarjeta
La lógica de validación puede dar falsos positivos/negativos.

### 5. Manejo de URLs simple.ripley.com.pe
Normalización inconsistente de URLs.

## Soluciones Propuestas

### 1. Mejorar Selectores CSS

**Selectores para Precio Público (ampliados):**
```php
$publicPriceSelectors = [
    // Selectores existentes
    '.product-internet-price-not-best .product-price',
    '.catalog-prices__offer-price',
    '.product-price__current',
    '.price__main',
    '.product-price__final-price',
    '.product-prices__price',
    '[itemprop="price"]',
    'meta[property="product:price:amount"]',
    
    // Nuevos selectores para estructura actual
    '.product-price[data-price-type="internet"]',
    '.price-internet',
    '.catalog-price--internet',
    '.product-card__price--internet',
    '[data-testid="internet-price"]',
    '.price-section .price-value:not(.price-card)',
    '.pricing-container .current-price',
    '.offer-price-container .price',
    '.ripley-price-internet',
    '.internet-price-display',
];
```

**Selectores para Precio de Tarjeta (ampliados):**
```php
$cardPriceSelectors = [
    // Selectores existentes
    '.product-ripley-price .product-price',
    '.catalog-prices__card-price',
    '.product-price__card',
    '.price-card',
    '.tarjeta-ripley-price',
    '.product-price__card-price',
    '.product-prices__card-price',
    '[data-testid="card-price"]',
    
    // Nuevos selectores más específicos
    '.product-price[data-price-type="ripley-card"]',
    '.price-tarjeta-ripley',
    '.catalog-price--ripley',
    '.product-card__price--ripley',
    '[data-testid="ripley-card-price"]',
    '.price-section.price-card .price-value',
    '.pricing-container .ripley-price',
    '.ripley-card-price',
    '.tarjeta-ripley .price',
    '.ripley-price-section .price',
    '.card-price-container .price-value',
    '[data-payment-method="ripley"] .price',
];
```

### 2. Mejorar Validación de Precio de Tarjeta

**Método validateRipleyCardPriceImproved():**
```php
private function validateRipleyCardPriceImproved(Crawler $crawler, array $cardPrices): bool
{
    // 1. Si no hay precios extraídos, no es válido
    if (empty($cardPrices)) {
        return false;
    }

    // 2. Verificar existencia de elementos de precio tarjeta
    $cardPriceElements = $crawler->filter('.product-ripley-price, .catalog-prices__card-price, .product-price__card, .price-card, .tarjeta-ripley-price, .product-price__card-price, .product-prices__card-price, [data-testid="card-price"], .ripley-card-price, [data-payment-method="ripley"]');
    
    if ($cardPriceElements->count() === 0) {
        return false;
    }

    // 3. Validar contenido específico
    foreach ($cardPriceElements as $element) {
        $text = trim($element->textContent);
        $parentText = strtolower(trim($element->parentNode->textContent ?? ''));
        
        // Patrones de precios válidos
        if (preg_match('/^(S\/\s*)?\d{1,3}([.,]\d{3})*([.,]\d{2})?$/', $text)) {
            // Verificar contexto de tarjeta Ripley
            if (
                stripos($parentText, 'tarjeta') !== false ||
                stripos($parentText, 'ripley') !== false ||
                $element->hasAttribute('data-payment-method') ||
                str_contains($element->getAttribute('class') ?? '', 'ripley')
            ) {
                // Excluir placeholders
                if (
                    !preg_match('/placeholder|no disponible|agotado|proximamente/i', $text) &&
                    !preg_match('/placeholder|no disponible|agotado|proximamente/i', $parentText)
                ) {
                    return true;
                }
            }
        }
    }

    return false;
}
```

### 3. Mejorar Manejo de Errores y Mensajes

**Mensajes de error mejorados en OfferAlertController.php:**
```php
// Validación mejorada para precio de tarjeta
if ($priceType === 'cmr') {
    if ($cmrPrice === null || $cmrPrice === false) {
        $cardName = match ($store) {
            'ripley' => 'Tarjeta Ripley',
            'oechsle' => 'Tarjeta Oh',
            'sodimac' => 'Única/CMR',
            'promart' => 'Tarjeta Oh',
            default => 'CMR',
        };
        
        $customMessage = match ($store) {
            'ripley' => "Este producto no tiene precio con {$cardName} disponible. Muchos productos de Ripley solo muestran precio público. Por favor, selecciona 'Precio Público' para monitorear este producto.",
            default => "Este producto no tiene precio con {$cardName} disponible. Por favor, selecciona 'Precio Público' para monitorear este producto.",
        };
        
        return back()
            ->withErrors(['msg' => $customMessage])
            ->withInput();
    }
}
```

## Implementación Prioritaria

1. **Configurar servidor Puppeteer** en localhost:3001
2. **Implementar selectores CSS mejorados** en scrapeRipley()
3. **Reemplazar validateRipleyCardPrice()** con versión mejorada
4. **Actualizar mensajes de error** en OfferAlertController
5. **Mejorar normalización de URLs**
6. **Agregar logging detallado** para debugging

## Test Case Específico

URL: https://simple.ripley.com.pe/adaptador-apple-20w-2065356902093p?color_80=blanco&s=mdco
- Público esperado: 109 soles
- Tarjeta Ripley esperado: 79 soles

## Archivos a Modificar

1. OfferPriceScraperService.php - Método scrapeRipley()
2. OfferAlertController.php - Validación de precio tarjeta  
3. index.blade.php - Mensajes de advertencia
4. config/services.php - Configuración Puppeteer
5. Nuevo: puppeteer-server.js - Servidor Puppeteer

Esta implementación mejorada debería resolver los problemas actuales y proporcionar una extracción de precios más robusta para Ripley.
