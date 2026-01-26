<?php

/**
 * Mejoras específicas para el método scrapeRipley en OfferPriceScraperService.php
 * 
 * Reemplazar el método scrapeRipley() existente con esta versión mejorada
 */

private function scrapeRipley(Crawler $crawler, string $url): array
{
    $data = $this->parseJsonLd($crawler);
    
    // Selectores mejorados para precio público (precio Internet)
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
        
        // Nuevos selectores para estructura actual de Ripley
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
        '.product-page-price .price',
        '.main-price-container .price',
    ];
    
    // Selectores mejorados para precio de tarjeta Ripley
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
        
        // Nuevos selectores más específicos para Ripley
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
        '.ripley-tarjeta-price',
        '.price-card-ripley',
    ];
    
    // Selectores para precio anterior/tachado
    $listPriceSelectors = [
        '.catalog-prices__list-price',
        '.product-price__original',
        '.price__original',
        '.product-price__before',
        '.product-prices__list-price',
        '.price-before',
        '.original-price',
        '.list-price',
    ];

    $publicPrices = $this->gatherPrices($crawler, $publicPriceSelectors);
    $cardPrices = $this->gatherPrices($crawler, $cardPriceSelectors);
    $listPrices = $this->gatherPrices($crawler, $listPriceSelectors);

    // Extraer precios desde JSON-LD mejorado
    if (isset($data['offers']['sale_price'])) {
        $publicPrices[] = $this->toNumber($data['offers']['sale_price']);
    } else if (isset($data['offers']['price'])) {
        $publicPrices[] = $this->toNumber($data['offers']['price']);
    }
    
    // Buscar precios en múltiples ofertas del JSON-LD con detección mejorada
    if (isset($data['offers']) && is_array($data['offers'])) {
        foreach ($data['offers'] as $offer) {
            if (isset($offer['price'])) {
                $publicPrices[] = $this->toNumber($offer['price']);
            }
            if (isset($offer['priceSpecification']) && is_array($offer['priceSpecification'])) {
                foreach ($offer['priceSpecification'] as $spec) {
                    if (isset($spec['price'])) {
                        $specName = strtolower($spec['name'] ?? '');
                        // Detección mejorada de precios de tarjeta
                        if (
                            stripos($specName, 'tarjeta') !== false || 
                            stripos($specName, 'ripley') !== false ||
                            stripos($specName, 'card') !== false
                        ) {
                            $cardPrices[] = $this->toNumber($spec['price']);
                        } else {
                            $publicPrices[] = $this->toNumber($spec['price']);
                        }
                    }
                }
            }
        }
    }

    // Extraer precios desde data attributes y JSON embebido
    $this->extractRipleyPricesFromDataAttributes($crawler, $publicPrices, $cardPrices);
    
    // Extraer precios usando patrones regex mejorados
    $this->extractRipleyPricesFromText($crawler, $publicPrices, $cardPrices);

    // Determinar el mejor precio público (excluyendo precios de tarjeta)
    $publicCandidates = $publicPrices;
    if (!empty($cardPrices)) {
        $publicCandidates = array_values(array_filter($publicPrices, function ($price) use ($cardPrices) {
            foreach ($cardPrices as $card) {
                if ($card !== null && $price !== null && abs($price - $card) < 0.01) {
                    return false;
                }
            }
            return true;
        }));
    }

    $publicPreferred = $this->firstNonNull([
        $this->toNumber($data['offers']['price'] ?? null),
        $this->bestPrice($publicCandidates),
        $this->bestPrice($publicPrices),
    ]);
    
    // Validación mejorada de precio de tarjeta
    $hasValidCardPrice = $this->validateRipleyCardPriceImproved($crawler, $cardPrices);
    
    $cardPreferred = null;
    if ($hasValidCardPrice) {
        $cardPreferred = $this->firstNonNull([
            $this->bestPrice($cardPrices),
        ]);
    }

    // Guardar precios para referencia
    if ($publicPreferred !== null) {
        $this->htmlPricePublic = $publicPreferred;
    }
    // Solo guardar precio de tarjeta si es válido
    if ($cardPreferred !== null && $hasValidCardPrice) {
        $this->htmlPriceCmr = $cardPreferred;
    } else {
        $this->htmlPriceCmr = null; // Explícitamente null si no hay precio válido
    }

    $title = $data['name'] ?? $this->textFirst($crawler, 'h1', 'Producto Ripley');
    $image = $data['image'] ?? $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');
    if (is_array($image)) {
        $image = $image[0] ?? null;
    }
    if (!$image) {
        $image = $this->imageFirst($crawler, 'meta[property="og:image:secure_url"]', 'content');
    }

    // Logging para debugging
    \Log::info('ripley_scraping_results', [
        'url' => $url,
        'public_prices_found' => count($publicPrices),
        'card_prices_found' => count($cardPrices),
        'has_valid_card_price' => $hasValidCardPrice,
        'public_price' => $publicPreferred,
        'card_price' => $cardPreferred,
        'title' => $title,
    ]);

    return $this->buildPayload(
        $title, 
        array_merge($publicPrices, $cardPrices), 
        $image, 
        'ripley', 
        $url, 
        $publicPreferred, 
        $publicPrices, 
        $cardPrices
    );
}


/**
 * Validación mejorada para precios de tarjeta Ripley
 */
private function validateRipleyCardPriceImproved(Crawler $crawler, array $cardPrices): bool
{
    // 1. Si no hay precios extraídos, no es válido
    if (empty($cardPrices)) {
        return false;
    }

    // 2. Verificar existencia de elementos de precio tarjeta con selectores mejorados
    $cardPriceElements = $crawler->filter(
        '.product-ripley-price, .catalog-prices__card-price, .product-price__card, .price-card, ' .
        '.tarjeta-ripley-price, .product-price__card-price, .product-prices__card-price, ' .
        '[data-testid="card-price"], .ripley-card-price, [data-payment-method="ripley"], ' .
        '.price-tarjeta-ripley, .catalog-price--ripley, .ripley-price-section'
    );
    
    if ($cardPriceElements->count() === 0) {
        return false;
    }

    // 3. Validar contenido específico de cada elemento
    foreach ($cardPriceElements as $element) {
        $text = trim($element->textContent);
        $parentText = strtolower(trim($element->parentNode->textContent ?? ''));
        $classAttr = strtolower($element->getAttribute('class') ?? '');
        
        // Patrones de precios válidos
        if (preg_match('/^(S\/\s*)?\d{1,3}([.,]\d{3})*([.,]\d{2})?$/', $text)) {
            // Verificar contexto de tarjeta Ripley (más robusto)
            $hasRipleyContext = (
                stripos($parentText, 'tarjeta') !== false ||
                stripos($parentText, 'ripley') !== false ||
                stripos($text, 'ripley') !== false ||
                $element->hasAttribute('data-payment-method') ||
                str_contains($classAttr, 'ripley') ||
                str_contains($classAttr, 'card') ||
                $element->closest('[data-payment-method="ripley"]') !== null
            );
            
            if ($hasRipleyContext) {
                // Excluir placeholders y textos no válidos
                $isInvalidText = (
                    preg_match('/placeholder|no disponible|agotado|proximamente|sin precio/i', $text) ||
                    preg_match('/placeholder|no disponible|agotado|proximamente/i', $parentText) ||
                    (float) str_replace(['S/', ',', ' '], '', $text) <= 0
                );
                
                if (!$isInvalidText) {
                    return true;
                }
            }
        }
    }

    return false;
}

