<?php

namespace App\Modules\Utilities\OfferAlerts\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Traits\BrowserSimulationTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class OfferPriceScraperService
{
    use BrowserSimulationTrait;

    private Client $client;
    private ?float $htmlPricePublic = null;
    private ?float $htmlPriceCmr = null;
    private array $debugMatches = [];
    private ?float $oechsleApiCardPrice = null;
    private bool $sodimacTypedPricesPresent = false;
    private bool $sodimacTypedCmrMissing = false;
    private bool $sodimacNextDataPricesPresent = false;
    private ?string $currentStore = null;
    private ?string $proxyEndpoint;
    private ?string $proxyKey;
    private ProxyRotationService $proxyRotator;

    public function __construct(
        ?Client $client = null, 
        ?ProxyRotationService $proxyRotator = null
    ) {
        $this->client = $client ?: new Client([
            'timeout' => 12,
            'http_errors' => false,
            'verify' => false,
            'cookies' => true, // Enable cookie jar
        ]);
        $this->proxyEndpoint = config('services.scraper.proxy_url');
        $this->proxyKey = config('services.scraper.proxy_key');
        
        $this->proxyRotator = $proxyRotator ?: app(ProxyRotationService::class);
    }

    public function detectStore(string $url): string
    {
        $u = strtolower($url);
        return match (true) {
            Str::contains($u, 'falabella') => 'falabella',
            Str::contains($u, 'ripley') => 'ripley',
            Str::contains($u, 'oechsle') => 'oechsle',
            Str::contains($u, 'sodimac') => 'sodimac',
            Str::contains($u, 'promart') => 'promart',
            Str::contains($u, 'mercadolibre') || Str::contains($u, 'mercado') => 'mercado_libre',
            default => 'desconocido',
        };
    }

    /**
     * Scrapea datos del producto y retorna el mejor precio encontrado (lista, descuento o precio tarjeta).
     */
    public function fetchProduct(string $url): array
    {
        $store = $this->detectStore($url);
        $this->currentStore = $store;
        $this->oechsleApiCardPrice = null;
        $this->htmlPricePublic = null;
        $this->htmlPriceCmr = null;
        $this->debugMatches = [];
        $this->sodimacTypedPricesPresent = false;
        $this->sodimacTypedCmrMissing = false;
        $this->sodimacNextDataPricesPresent = false;
        try {
            $html = $this->fetchHtml($url);
            if ($store === 'promart' && trim($html) !== '') {
                $dir = storage_path('app/scrape/promart');
                File::ensureDirectoryExists($dir);
                $filename = sprintf(
                    'promart_debug_%s_%s.html',
                    date('Ymd_His'),
                    substr(sha1($url), 0, 8)
                );
                File::put($dir . DIRECTORY_SEPARATOR . $filename, $html);
            }
            if ($store === 'mercado_libre' && trim($html) !== '') {
                $dir = storage_path('app/scrape/mercado_libre');
                File::ensureDirectoryExists($dir);
                $filename = sprintf(
                    'mercado_libre_debug_%s_%s.html',
                    date('Ymd_His'),
                    substr(sha1($url), 0, 8)
                );
                File::put($dir . DIRECTORY_SEPARATOR . $filename, $html);
            }
            if (
                $store === 'sodimac' &&
                $url === 'https://www.sodimac.com.pe/sodimac-pe/articulo/118065399/Pack-x-2-Almohadas-Ventus-Firm-70x50cm/118065400' &&
                trim($html) !== ''
            ) {
                $dir = storage_path('app/scrape/sodimac');
                File::ensureDirectoryExists($dir);
                $filename = sprintf(
                    'sodimac_debug_%s_%s.html',
                    date('Ymd_His'),
                    substr(sha1($url), 0, 8)
                );
                File::put($dir . DIRECTORY_SEPARATOR . $filename, $html);
            }
            if ($store === 'oechsle') {
                $productId = $this->extractOechsleProductIdFromHtml($html);
                if (!$productId) {
                    $productId = $this->extractOechsleProductIdFromUrl($url);
                }
                if ($productId) {
                    $this->oechsleApiCardPrice = $this->fetchOechsleCardPriceFromApi($productId);
                }
            }
            $this->extractPricesFromHtml($html);
            // Debug opcional: guardar HTML para inspeccionar sin romper la pГЎgina
            if (config('app.debug')) {
                file_put_contents(storage_path('app/scrape-offer.html'), $html);
                if (!empty($this->debugMatches)) {
                    file_put_contents(
                        storage_path('app/scrape-offer-debug.log'),
                        json_encode($this->debugMatches, JSON_PRETTY_PRINT)
                    );
                }
            }
            $crawler = new Crawler($html);

            return match ($store) {
                'falabella' => $this->scrapeFalabella($crawler, $url),
                'ripley' => $this->scrapeRipley($crawler, $url),
                'oechsle' => $this->scrapeOechsle($crawler, $url),
                'sodimac' => $this->scrapeSodimac($crawler, $url),
                'promart' => $this->scrapePromart($crawler, $url),
                'mercado_libre' => $this->scrapeMercadoLibre($crawler, $url),
                default => $this->scrapeGeneric($crawler, $url, $store),
            };
        } catch (\Throwable $e) {
            // Si es Ripley, intentar con Puppeteer para saltar bloqueos JS/anti-bot
            if ($store === 'ripley') {
                $puppeteer = $this->fetchWithPuppeteer($url);
                if ($puppeteer) {
                    return $puppeteer;
                }
            }
            // Fallback si falla la red o el parseo: usa el precio hallado en HTML si existe
            return [
                'title' => 'Producto',
                'price' => $this->htmlPricePublic ?? $this->htmlPriceCmr,
                'public_price' => $this->htmlPricePublic,
                'cmr_price' => $this->htmlPriceCmr,
                'image_url' => 'https://via.placeholder.com/320x240.png?text=Producto',
                'store' => $store,
                'url' => $url,
            ];
        }
    }

    private function fetchHtml(string $url): string
    {
        $attempts = [$url, $this->normalizeRipleyUrl($url)];
        foreach ($attempts as $tryUrl) {
            if (!$tryUrl) {
                continue;
            }
            try {
                // Get options with fresh cookies + headers + log
                $requestOptions = $this->getSimulatedOptions($tryUrl, 'offer_scrape');
                
                $response = $this->client->get($tryUrl, $requestOptions);
                $status = $response->getStatusCode();
                
                \Log::info('offer_scrape_response', [
                    'url' => $tryUrl,
                    'status' => $status,
                    'len' => $response->hasHeader('Content-Length') ? $response->getHeader('Content-Length')[0] : null,
                ]);

                if ($status === 200) {
                    return (string) $response->getBody();
                }
            } catch (\Throwable $e) {
                \Log::error('offer_scrape_error', [
                    'url' => $tryUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Intentar con proxy/JS si está configurado
        if ($this->proxyEndpoint && $this->proxyKey) {
            $proxyUrl = $this->buildProxyUrl($url);
            try {
                $requestOptions = $this->getSimulatedOptions($url, 'offer_scrape_proxy');

                $response = $this->client->get($proxyUrl, $requestOptions);
                $status = $response->getStatusCode();
                
                \Log::info('offer_scrape_proxy_response', [
                    'url' => $url,
                    'proxy' => $proxyUrl,
                    'status' => $status,
                    'len' => $response->hasHeader('Content-Length') ? $response->getHeader('Content-Length')[0] : null,
                ]);

                if ($status === 200) {
                    return (string) $response->getBody();
                }
            } catch (\Throwable $e) {
                \Log::error('offer_scrape_proxy_error', [
                    'url' => $url,
                    'proxy' => $proxyUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Finalmente, intentar con rotación de IPs propias si está habilitado
        if ($this->proxyRotator->isEnabled()) {
            $proxy = $this->proxyRotator->getRandomProxy();
            if ($proxy) {
                try {
                    $requestOptions = $this->getSimulatedOptions($url, 'offer_scrape_rotated_proxy');
                    $requestOptions['proxy'] = $proxy;

                    $response = $this->client->get($url, $requestOptions);
                    $status = $response->getStatusCode();
                    
                    \Log::info('offer_scrape_rotated_proxy_response', [
                        'url' => $url,
                        'proxy' => $proxy,
                        'status' => $status,
                    ]);

                    if ($status === 200) {
                        return (string) $response->getBody();
                    }
                } catch (\Throwable $e) {
                    \Log::error('offer_scrape_rotated_proxy_error', [
                        'url' => $url,
                        'proxy' => $proxy,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        throw new \RuntimeException('No se pudo obtener HTML del producto. (Status ' . ($status ?? 'Unknown') . ')');
    }

    private function normalizeRipleyUrl(string $url): ?string
    {
        $lower = strtolower($url);
        if (str_contains($lower, 'simple.ripley.com.pe')) {
            return str_ireplace('simple.ripley.com.pe', 'www.ripley.com.pe', $url);
        }
        return $url;
    }


    private function buildProxyUrl(string $targetUrl): string
    {
        // Ejemplo de ScraperAPI: https://api.scraperapi.com?api_key=KEY&url=...
        if (str_contains($this->proxyEndpoint, '{URL}')) {
            return str_replace(['{URL}', '{KEY}'], [urlencode($targetUrl), urlencode($this->proxyKey)], $this->proxyEndpoint);
        }
        $sep = str_contains($this->proxyEndpoint, '?') ? '&' : '?';
        return $this->proxyEndpoint . $sep . 'api_key=' . urlencode($this->proxyKey) . '&url=' . urlencode($targetUrl);
    }

/**
     * Intenta renderizar la página con Puppeteer vía API local.
     */
    private function fetchWithPuppeteer(string $url): ?array
    {
        try {
            // URL de tu API local (ajusta según tu configuración)
            $localApiUrl = config('services.puppeteer.local_api_url', 'http://localhost:3001/scrape/ripley');
            
            $response = $this->client->post($localApiUrl, [
                'json' => [
                    'url' => $url,
                    'searchParams' => []
                ],
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Utilitary-Server/1.0',
                    'X-API-Key' => config('services.puppeteer.api_key', 'utilitary-secret-key-2024')
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                \Log::error('puppeteer_api_error', [
                    'url' => $url,
                    'status' => $response->getStatusCode(),
                    'response' => (string) $response->getBody()
                ]);
                return null;
            }

            $data = json_decode((string) $response->getBody(), true);
            
            if (!$data || !$data['success']) {
                \Log::error('puppeteer_api_failed', [
                    'url' => $url,
                    'response' => $data
                ]);
                return null;
            }

            $products = $data['data'] ?? [];
            if (empty($products)) {
                \Log::warning('puppeteer_no_products', ['url' => $url]);
                return null;
            }

            // Tomar el primer producto encontrado
            $product = $products[0];
            $price = $this->toNumber($product['price'] ?? null);
            $publicPrice = $this->toNumber($product['price'] ?? null);
            $cardPrice = null; // Ripley no suele mostrar precio tarjeta en el scraping

            return [
                'title' => $product['name'] ?? 'Producto Ripley',
                'price' => $price,
                'public_price' => $publicPrice ?? $price,
                'cmr_price' => $cardPrice,
                'image_url' => $product['image'] ?? 'https://via.placeholder.com/320x240.png?text=Producto',
                'store' => 'ripley',
                'url' => $product['link'] ?? $url,
            ];
            
        } catch (\Throwable $e) {
            \Log::error('puppeteer_api_exception', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function cleanupPuppeteerProfiles(string $baseDir): void
    {
        if (!is_dir($baseDir)) {
            return;
        }

        $ttl = 60 * 60; // 1 hora
        foreach (glob($baseDir . DIRECTORY_SEPARATOR . 'profile_*', GLOB_ONLYDIR) as $dir) {
            if ((time() - filemtime($dir)) > $ttl) {
                try {
                    File::deleteDirectory($dir);
                } catch (\Throwable $e) {
                    \Log::warning('puppeteer_profile_cleanup_failed', ['dir' => $dir, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    private function scrapeFalabella(Crawler $crawler, string $url): array
    {
        $data = $this->parseJsonLd($crawler);
        $publicPrices = $this->gatherPricesExcludingClasses($crawler, [
            '.fb-product-cta__prices__price-final',
            '.product-info__price',
            '.product-prices__value:not(.product-prices__value--cmr)',
            '[data-test-id="product-price"]',
            '[itemprop="price"]',
            'meta[property="product:price:amount"]',
        ], ['crossed']);
        $crossedPrices = $this->gatherPrices($crawler, [
            '.crossed',
            '.price-before',
            '.price-old',
            '.product-price__before',
            '.fb-product-cta__prices__price-regular',
        ]);
        $cmrPrices = $this->gatherPrices($crawler, [
            '.product-prices__value--cmr',
            '.fb-product-cta__prices__price-cmr',
            '[data-test-id="cmr-price"]',
            '[data-testid="cmr-price"]',
        ]);

        $offerPrices = $this->extractOfferPrices($data);
        $publicCandidates = $publicPrices;
        if (!empty($cmrPrices)) {
            $publicCandidates = array_values(array_filter($publicPrices, function ($price) use ($cmrPrices) {
                foreach ($cmrPrices as $cmr) {
                    if ($cmr !== null && $price !== null && abs($price - $cmr) < 0.01) {
                        return false;
                    }
                }
                return true;
            }));
        }

        $inferredPublic = null;
        $inferredCmr = null;
        if (empty($cmrPrices) && count($offerPrices) >= 2) {
            $maxOffer = $this->maxPrice($offerPrices);
            $minOffer = $this->minPrice($offerPrices);
            if (!empty($crossedPrices) && $this->priceMatchesAny($maxOffer, $crossedPrices)) {
                $inferredPublic = $minOffer;
                $inferredCmr = null;
            } else {
                $inferredPublic = $maxOffer;
                $inferredCmr = $minOffer;
            }
        }

        $publicPreferred = $this->firstNonNull([
            $this->bestPrice($publicCandidates),
            $this->bestPrice($publicPrices),
            $inferredPublic,
            empty($cmrPrices) ? $this->minPrice($offerPrices) : $this->maxPrice($offerPrices),
        ]);
        $cmrPreferred = $this->firstNonNull([
            $this->bestPrice($cmrPrices),
            $inferredCmr,
        ]);

        if ($publicPreferred !== null) {
            $this->htmlPricePublic = $publicPreferred;
        }
        if ($cmrPreferred !== null) {
            $this->htmlPriceCmr = $cmrPreferred;
        }

        $title = $data['name'] ?? $this->textFirst($crawler, 'h1', 'Producto Falabella');
        $image = $data['image'] ?? $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'meta[name="twitter:image"]', 'content');
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'meta[property="og:image:secure_url"]', 'content');
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'img[data-testid="product-image"]', 'src');
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'img[data-test-id="product-image"]', 'src');
        }

        $prices = array_values(array_filter(array_merge($publicPrices, $cmrPrices)));

        return $this->buildPayload(
            $title,
            $prices,
            $image,
            'falabella',
            $url,
            $publicPreferred,
            $publicPrices,
            $cmrPrices
        );
    }

    private function scrapeRipley(Crawler $crawler, string $url): array
    {
        $data = $this->parseJsonLd($crawler);
        $prices = $this->gatherPrices($crawler, [
            '.product-price__final-price',
            '.product-prices__price',
            '[itemprop="price"]',
            'meta[property="product:price:amount"]',
        ]);

        if (isset($data['offers']['price'])) {
            $prices[] = $this->toNumber($data['offers']['price']);
        }

        $preferred = $this->firstNonNull([
            $this->toNumber($data['offers']['price'] ?? null),
            $prices[0] ?? null,
        ]);

        $title = $data['name'] ?? $this->textFirst($crawler, 'h1', 'Producto Ripley');
        $image = $data['image'] ?? $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');

        return $this->buildPayload($title, $prices, $image, 'ripley', $url, $preferred, $prices);
    }

    private function scrapeOechsle(Crawler $crawler, string $url): array
    {
        $publicPrices = $this->gatherPricesExcludingClasses($crawler, [
            '.product-info-price .price',
            '.price-box .price',
            'meta[property="product:price:amount"]',
        ], ['crossed', 'old', 'before']);
        $cardPrices = $this->gatherPrices($crawler, [
            '.product-info-price .oh-price',
            '.product-info-price .card-price',
            '.product-info-price .credit-price',
            '#containerPrice .priceTOh',
            '#containerPrice .priceTOh .labeled',
            '[data-testid="card-price"]',
        ]);
        if ($this->oechsleApiCardPrice !== null) {
            $cardPrices[] = $this->oechsleApiCardPrice;
        }
        $crossedPrices = $this->gatherPrices($crawler, [
            '.crossed',
            '.price-before',
            '.price-old',
            '.old-price',
        ]);

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

        $publicPreferred = $this->bestPrice($publicCandidates) ?? $this->bestPrice($publicPrices);
        $cardPreferred = $this->oechsleApiCardPrice ?? $this->bestPrice($cardPrices);

        if ($publicPreferred === null && !empty($publicPrices) && !empty($crossedPrices)) {
            $maxPublic = $this->maxPrice($publicPrices);
            if ($this->priceMatchesAny($maxPublic, $crossedPrices)) {
                $publicPreferred = $this->minPrice($publicPrices);
            }
        }

        if ($publicPreferred !== null) {
            $this->htmlPricePublic = $publicPreferred;
        }
        if ($cardPreferred !== null) {
            $this->htmlPriceCmr = $cardPreferred;
        }

        $title = $this->textFirst($crawler, 'h1', 'Producto Oechsle');
        $image = $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');

        return $this->buildPayload($title, $publicPrices, $image, 'oechsle', $url, $publicPreferred, $publicPrices, $cardPrices);
    }

    private function scrapeSodimac(Crawler $crawler, string $url): array
    {
        $data = $this->parseJsonLd($crawler);
        $publicPrices = $this->gatherPricesExcludingClasses($crawler, [
            '.product-prices__value:not(.product-prices__value--old):not(.product-prices__value--before):not(.product-prices__value--list):not(.crossed)',
            '.fbra_text--product-price',
            'meta[property="product:price:amount"]',
        ], ['crossed', 'old', 'before']);

        $cardPrices = $this->gatherPricesExcludingText($crawler, [
            '.product-prices__card',
            '.product-prices__value--cmr',
            '.product-prices__value--unica',
            '[data-testid="card-price"]',
        ], ['cuota', 'cuotas', 'meses', 'interes', 'intereses']);

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

        $typedPublic = $this->htmlPricePublic;
        $preferred = $this->firstNonNull([
            $typedPublic,
            $this->toNumber($data['offers']['price'] ?? null),
            $this->bestPrice($publicCandidates),
            $this->bestPrice($publicPrices),
        ]);

        $cardPreferred = $this->firstNonNull([
            $this->bestPrice($cardPrices),
            $this->htmlPriceCmr,
        ]);

        if ($preferred !== null && $this->htmlPricePublic === null) {
            $this->htmlPricePublic = $preferred;
        }
        if ($cardPreferred !== null) {
            $this->htmlPriceCmr = $cardPreferred;
        } else {
            $this->htmlPriceCmr = null;
        }

        $title = $data['name'] ?? $this->textFirst($crawler, 'h1', 'Producto Sodimac');
        $image = $data['image'] ?? $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');
        if (is_array($image)) {
            $image = $image[0] ?? null;
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'meta[property="og:image:secure_url"]', 'content');
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'meta[name="twitter:image"]', 'content');
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'img[itemprop="image"]', 'src');
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'img[data-testid="product-image"]', 'src');
        }
        if (!$image) {
            $image = $this->imageFirst($crawler, 'img.product-image', 'src');
        }

        return $this->buildPayload($title, $publicPrices, $image, 'sodimac', $url, $preferred, $publicPrices, $cardPrices);
    }

    private function scrapePromart(Crawler $crawler, string $url): array
    {
        $data = $this->parseJsonLd($crawler);
        $publicSelectors = [
            'meta[property="product:price:amount"]',
        ];
        $publicPrices = [];
        foreach ($publicSelectors as $selector) {
            $values = [];
            $crawler->filter($selector)->each(function ($node) use (&$values) {
                $values[] = $this->toNumber($node->attr('content') ?? $node->text());
            });
            $values = array_filter($values, fn ($p) => $p !== null && $p > 0);
            if (!empty($values)) {
                $publicPrices = array_merge($publicPrices, array_values($values));
            }
            \Log::info('promart_public_price_selector', [
                'url' => $url,
                'selector' => $selector,
                'values' => array_values($values),
            ]);
        }

        // Intentar obtener precio Tarjeta Oh vía API
        $cardApiPrice = null;
        try {
            // Extraer SKU del HTML
            $html = $crawler->html();
            $skuId = null;
            // Buscar en inputs ocultos o llamadas JS
            if (preg_match('/id="___rc-p-sku-ids"\s+value="(\d+)"/', $html, $matches)) {
                $skuId = $matches[1];
            } elseif (preg_match('/buyButton\((\d+),/', $html, $matches)) {
                $skuId = $matches[1];
            } elseif (preg_match('/skuId["\']?:["\']?(\d+)["\']?/', $html, $matches)) {
                $skuId = $matches[1];
            }

            if ($skuId) {
                // Llamar a la API de VTEX
                // sc=2 es canal de ventas online usual
                $apiUrl = "https://www.promart.pe/api/catalog_system/pub/products/search/?fq=skuId:{$skuId}&sc=2";
                \Log::info('promart_api_request', [
                    'product_url' => $url,
                    'sku_id' => $skuId,
                    'api_url' => $apiUrl,
                ]);
                
                $response = $this->client->get($apiUrl, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 5,
                    'verify' => false
                ]);

                $apiData = json_decode((string) $response->getBody(), true);
                
                if (!empty($apiData[0]['items'][0]['sellers'][0]['commertialOffer'])) {
                    $offer = $apiData[0]['items'][0]['sellers'][0]['commertialOffer'];
                    $basePrice = (float) ($offer['PriceWithoutDiscount'] ?? $offer['Price'] ?? 0);
                    $teasers = $offer['PromotionTeasers'] ?? [];
                    
                    foreach ($teasers as $teaser) {
                        // Buscar ID de medio de pago 203 (Tarjeta Oh)
                        $isOh = false;
                        if (isset($teaser['Conditions']['Parameters'])) {
                            foreach ($teaser['Conditions']['Parameters'] as $param) {
                                if (isset($param['Name']) && $param['Name'] === 'PaymentMethodId' && $param['Value'] == '203') {
                                    $isOh = true;
                                    break;
                                }
                            }
                        }

                        if ($isOh && isset($teaser['Effects']['Parameters'])) {
                            foreach ($teaser['Effects']['Parameters'] as $effect) {
                                if (($effect['Name'] ?? '') === 'PromotionalPriceTableItemsDiscount') {
                                    $discount = (float) ($effect['Value'] ?? 0);
                                    if ($discount > 0 && $basePrice > 0) {
                                        $cardApiPrice = $basePrice - $discount;
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('promart_api_error', ['url' => $url, 'error' => $e->getMessage()]);
        }

        $cardPrices = $this->gatherPrices($crawler, [
            '.product-price__card',
            '.product-price__oh',
            '.price-oh',
            '.price-toh',
            '.js-price-toh',
            '.card-price',
            '[data-testid="card-price"]',
        ]);

        if ($cardApiPrice !== null) {
            array_unshift($cardPrices, $cardApiPrice);
        }

        $preferred = $this->firstNonNull([
            $this->maxPrice($publicPrices),
            $publicPrices[0] ?? null,
        ]);

        $this->htmlPricePublic = $this->htmlPricePublic ?? $preferred;
        $this->htmlPriceCmr = $this->htmlPriceCmr ?? ($cardPrices[0] ?? null);

        $title = $data['name'] ?? $this->textFirst($crawler, 'h1', 'Producto Promart');
        $image = $data['image'] ?? $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');

        return $this->buildPayload($title, $publicPrices, $image, 'promart', $url, $preferred, $publicPrices, $cardPrices);
    }

    private function scrapeMercadoLibre(Crawler $crawler, string $url): array
    {
        $prices = $this->gatherPrices($crawler, [
            'meta[property="product:price:amount"]',
            '.ui-pdp-price__second-line .andes-money-amount__fraction',
            '.andes-money-amount__fraction',
        ]);
        $preferred = $prices[0] ?? null;
        $title = $this->textFirst($crawler, 'h1', 'Producto Mercado Libre');
        $image = $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');

        return $this->buildPayload($title, $prices, $image, 'mercado_libre', $url, $preferred, $prices);
    }

    private function scrapeGeneric(Crawler $crawler, string $url, string $store): array
    {
        $prices = $this->gatherPrices($crawler, [
            'meta[property="product:price:amount"]',
            'meta[name="twitter:data1"]',
            '.price, .current-price',
        ]);
        $preferred = $prices[0] ?? null;
        $title = $this->textFirst($crawler, 'h1', 'Producto');
        $image = $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');

        return $this->buildPayload($title, $prices, $image, $store ?: 'desconocido', $url, $preferred, $prices);
    }

    private function parseJsonLd(Crawler $crawler): array
    {
        $data = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function ($node) use (&$data) {
            $json = trim($node->text());
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                return;
            }
            if (isset($decoded['@type']) && Str::contains(strtolower($decoded['@type']), 'product')) {
                $data = $decoded;
            }
            if (isset($decoded[0]) && isset($decoded[0]['@type']) && Str::contains(strtolower($decoded[0]['@type']), 'product')) {
                $data = $decoded[0];
            }
        });
        return $data;
    }

    private function gatherPrices(Crawler $crawler, array $selectors): array
    {
        $prices = [];
        foreach ($selectors as $selector) {
            $crawler->filter($selector)->each(function ($node) use (&$prices) {
                $prices[] = $this->toNumber($node->attr('content') ?? $node->text());
            });
        }

        return array_filter($prices, fn ($p) => $p !== null && $p > 0);
    }

    private function gatherPricesExcludingClasses(Crawler $crawler, array $selectors, array $excludedClasses): array
    {
        $prices = [];
        foreach ($selectors as $selector) {
            $crawler->filter($selector)->each(function ($node) use (&$prices, $excludedClasses) {
                $classAttr = $node->attr('class') ?? '';
                foreach ($excludedClasses as $excluded) {
                    if ($excluded !== '' && str_contains($classAttr, $excluded)) {
                        return;
                    }
                }
                $prices[] = $this->toNumber($node->attr('content') ?? $node->text());
            });
        }

        return array_filter($prices, fn ($p) => $p !== null && $p > 0);
    }

    private function gatherPricesExcludingText(Crawler $crawler, array $selectors, array $excludedFragments): array
    {
        $prices = [];
        foreach ($selectors as $selector) {
            $crawler->filter($selector)->each(function ($node) use (&$prices, $excludedFragments) {
                $text = strtolower(trim($node->text() ?? ''));
                foreach ($excludedFragments as $fragment) {
                    if ($fragment !== '' && str_contains($text, $fragment)) {
                        return;
                    }
                }
                $prices[] = $this->toNumber($node->attr('content') ?? $node->text());
            });
        }

        return array_filter($prices, fn ($p) => $p !== null && $p > 0);
    }

    private function toNumber(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $clean = preg_replace('/[^0-9,\\.]/', '', $raw);
        if ($clean === '' || $clean === null) {
            return null;
        }

        // Si trae ambos separadores, asumimos coma como decimal.
        if (strpos($clean, '.') !== false && strpos($clean, ',') !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (strpos($clean, ',') !== false && strpos($clean, '.') === false) {
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function textFirst(Crawler $crawler, string $selector, string $fallback): string
    {
        try {
            $text = $crawler->filter($selector)->first()->text();
            return trim($text) ?: $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function imageFirst(Crawler $crawler, string $selector, string $attr): ?string
    {
        try {
            $value = $crawler->filter($selector)->first()->attr($attr);
            return $value ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildPayload(
        string $title,
        array $prices,
        ?string $image,
        string $store,
        string $url,
        ?float $preferredPrice = null,
        array $publicPrices = [],
        array $cmrPrices = []
    ): array
    {
        $publicBest = $this->firstNonNull([
            $this->htmlPricePublic,
            $preferredPrice,
            $this->bestPrice($publicPrices),
        ]);
        $cmrBest = $this->firstNonNull([
            $this->htmlPriceCmr,
            $this->bestPrice($cmrPrices),
        ]);
        if ($cmrBest === null) {
            $cmrBest = $publicBest;
        }
        $price = $preferredPrice ?? $this->bestPrice($prices) ?? $publicBest ?? $cmrBest;

        return [
            'title' => $title,
            'price' => $price ?? 0,
            'public_price' => $publicBest ?? $price,
            'cmr_price' => $cmrBest,
            'image_url' => $image ?: 'https://via.placeholder.com/320x240.png?text=Producto',
            'store' => $store,
            'url' => $url,
        ];
    }

    private function bestPrice(array $prices): ?float
    {
        $filtered = array_filter($prices, fn ($p) => $p !== null && $p > 0);
        if (empty($filtered)) {
            return null;
        }
        return round(min($filtered), 2);
    }

    private function firstNonNull(array $values): ?float
    {
        foreach ($values as $value) {
            if ($value !== null && $value > 0) {
                return round($value, 2);
            }
        }
        return null;
    }


    private function extractOfferPrices(array $data): array
    {
        if (!isset($data['offers'])) {
            return [];
        }

        $offers = $data['offers'];
        $prices = [];

        // offers puede ser objeto o array
        if (isset($offers['price'])) {
            $prices[] = $this->toNumber($offers['price']);
        }

        if (is_array($offers)) {
            foreach ($offers as $offer) {
                if (isset($offer['price'])) {
                    $prices[] = $this->toNumber($offer['price']);
                }
            }
        }

        $prices = array_filter($prices, fn ($p) => $p !== null && $p > 0);
        return array_values($prices);
    }

    private function minPrice(array $prices): ?float
    {
        if (empty($prices)) {
            return null;
        }
        return round(min($prices), 2);
    }

    private function maxPrice(array $prices): ?float
    {
        if (empty($prices)) {
            return null;
        }
        return round(max($prices), 2);
    }

    private function extractPricesFromHtml(string $html): void
    {
        $this->extractSodimacNextDataPrices($html);
        if ($this->sodimacNextDataPricesPresent && ($this->htmlPricePublic !== null || $this->htmlPriceCmr !== null)) {
            return;
        }
        $this->extractSodimacTypedPrices($html);
        $patternsPublic = [
            '/"price"\\s*:\\s*\\["?([0-9.,]+)"?\\]/i',
            '/"price"\\s*:\\s*"([0-9.,]+)"/i',
            '/price\\s*=\\s*"([0-9.,]+)"/i',
            '/"price"[^0-9]{0,20}([0-9.,]+)/i',
            '/"sellingPrice"\\s*:\\s*"([0-9.,]+)"/i',
            '/"productPrice"\\s*:\\s*"([0-9.,]+)"/i',
            '/"formattedPrice"\\s*":\\s*"S?\\/?\\s*([0-9.,]+)/i',
            '/"offerPrice"\\s*":\\s*"([0-9.,]+)/i',
            '/offerPrice"?:\\s*([0-9]+[.,]?[0-9]*)/i',
            '/(S\\/|S\\/)\\s*([0-9]+[.,][0-9]{2})/i',
        ];
        $patternsCmr = [
            '/"cardPrice"\\s*":\\s*"([0-9.,]+)/i',
            '/"cmrPrice"\\s*":\\s*"([0-9.,]+)/i',
            '/tarjeta\\s+(?:ripley|banco\\s+ripley)[^0-9]{0,80}([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)/i',
            '/(?:tc|t\\.c\\.)\\s+ripley[^0-9]{0,80}([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)/i',
            '/tarjeta\\s+oh!?[^0-9]{0,80}([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)/i',
            '/precio\\s+oh!?[^0-9]{0,80}([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)/i',
            '/(?:S\\/\\s*)?([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)\\s*(?:con\\s+)?tarjeta\\s+oh!?/i',
            '/([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)\\s*(?:S\\/\\s*)?(?:[^0-9]{0,40})?tarjeta\\s+oh!?/i',
            '/\\bunica\\b[^0-9]{0,80}([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)/i',
            '/\\bcmr\\b[^0-9]{0,80}([0-9]{1,3}(?:[\\.,][0-9]{3})*(?:[\\.,][0-9]{2})?)/i',
        ];

        foreach ($patternsPublic as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $value = $this->toNumber($m[1] ?? $m[2] ?? null);
                if ($value !== null) {
                    $this->htmlPricePublic = $this->htmlPricePublic ?? $value;
                    $this->debugMatches[] = ['pattern' => $pattern, 'raw' => $m[0], 'value' => $value, 'type' => 'public'];
                    break;
                }
            }
        }

        if ($this->currentStore === 'promart') {
            return;
        }

        if ($this->sodimacTypedPricesPresent && $this->sodimacTypedCmrMissing) {
            return;
        }

        foreach ($patternsCmr as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                if (stripos($m[0], 'jsx-') !== false) {
                    continue;
                }
                $lowerMatch = strtolower($m[0]);
                if (
                    str_contains($lowerMatch, 'recibe') ||
                    str_contains($lowerMatch, 'cashback') ||
                    str_contains($lowerMatch, 'primera compra') ||
                    str_contains($lowerMatch, 'primera compra online') ||
                    str_contains($lowerMatch, 'cuota') ||
                    str_contains($lowerMatch, 'cuotas') ||
                    str_contains($lowerMatch, 'meses') ||
                    str_contains($lowerMatch, 'interes') ||
                    str_contains($lowerMatch, 'intereses')
                ) {
                    continue;
                }
                $value = $this->toNumber($m[1] ?? $m[2] ?? null);
                if ($value !== null) {
                    $this->htmlPriceCmr = $this->htmlPriceCmr ?? $value;
                    $this->debugMatches[] = ['pattern' => $pattern, 'raw' => $m[0], 'value' => $value, 'type' => 'cmr'];
                    break;
                }
            }
        }
    }

    private function extractSodimacTypedPrices(string $html): void
    {
        $patterns = [
            'cmr' => '/"type"\\s*:\\s*"cmrPrice"[^}]*"price"\\s*:\\s*\\["?([0-9.,]+)"?\\]/i',
            'event' => '/"type"\\s*:\\s*"eventPrice"[^}]*"price"\\s*:\\s*\\["?([0-9.,]+)"?\\]/i',
            'normal' => '/"type"\\s*:\\s*"normalPrice"[^}]*"price"\\s*:\\s*\\["?([0-9.,]+)"?\\]/i',
        ];

        $found = [];
        foreach ($patterns as $label => $pattern) {
            if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($matches as $m) {
                $raw = $m[0][0] ?? '';
                $offset = $m[0][1] ?? 0;
                $window = substr($html, max(0, $offset - 400), 800);
                if (stripos($window, 'CES ORO') !== false) {
                    continue;
                }
                $value = $this->toNumber($m[1][0] ?? null);
                if ($value !== null) {
                    $found[$label] = $value;
                    $this->debugMatches[] = ['pattern' => $pattern, 'raw' => $raw, 'value' => $value, 'type' => $label];
                    break;
                }
            }
        }

        if (array_key_exists('cmr', $found)) {
            $this->htmlPriceCmr = $found['cmr'];
            $this->sodimacTypedPricesPresent = true;
            $this->sodimacTypedCmrMissing = false;
        }
        if (array_key_exists('event', $found)) {
            $this->htmlPricePublic = $found['event'];
            $this->sodimacTypedPricesPresent = true;
        } elseif (array_key_exists('normal', $found)) {
            $this->htmlPricePublic = $found['normal'];
            $this->sodimacTypedPricesPresent = true;
        }
        if ($this->sodimacTypedPricesPresent && !array_key_exists('cmr', $found)) {
            $this->htmlPriceCmr = null;
            $this->sodimacTypedCmrMissing = true;
        }
    }

    private function extractSodimacNextDataPrices(string $html): void
    {
        if (!preg_match('/<script id="__NEXT_DATA__" type="application\\/json">(.+?)<\\/script>/s', $html, $m)) {
            return;
        }

        $data = json_decode($m[1], true);
        if (!is_array($data)) {
            return;
        }

        $productData = $data['props']['pageProps']['productData'] ?? null;
        if (!is_array($productData)) {
            return;
        }

        $productSites = $productData['productSites'] ?? [];
        if (is_array($productSites) && !in_array('SODIMAC', $productSites, true)) {
            return;
        }

        $variantId = $data['query']['variantId'] ?? ($productData['currentVariant'] ?? null);
        $variants = $productData['variants'] ?? [];
        if (!is_array($variants) || empty($variants)) {
            return;
        }

        $variant = null;
        if ($variantId) {
            foreach ($variants as $candidate) {
                if (is_array($candidate) && (string) ($candidate['id'] ?? '') === (string) $variantId) {
                    $variant = $candidate;
                    break;
                }
            }
        }
        if (!$variant) {
            $variant = $variants[0] ?? null;
        }
        if (!is_array($variant)) {
            return;
        }

        $prices = $variant['prices'] ?? [];
        if (!is_array($prices) || empty($prices)) {
            return;
        }

        $public = null;
        $cmr = null;
        $publicPriority = ['eventprice', 'internetprice', 'publicprice', 'normalprice', 'price'];
        $publicBestIndex = null;

        foreach ($prices as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $type = strtolower((string) ($entry['type'] ?? ''));
            $crossed = (bool) ($entry['crossed'] ?? false);
            $rawPrice = null;
            if (isset($entry['price'][0])) {
                $rawPrice = $entry['price'][0];
            } elseif (isset($entry['price'])) {
                $rawPrice = $entry['price'];
            }
            $value = $this->toNumber(is_scalar($rawPrice) ? (string) $rawPrice : null);
            if ($value === null) {
                continue;
            }

            if ($type !== '' && (str_contains($type, 'cmr') || str_contains($type, 'card') || str_contains($type, 'unica'))) {
                $cmr = $cmr ?? $value;
                continue;
            }

            if ($crossed) {
                continue;
            }

            if ($type === '') {
                $public = $public ?? $value;
                continue;
            }

            if (in_array($type, $publicPriority, true)) {
                $currentIndex = array_search($type, $publicPriority, true);
                if ($publicBestIndex === null || $currentIndex < $publicBestIndex) {
                    $publicBestIndex = $currentIndex;
                    $public = $value;
                }
            }
        }

        if ($public !== null) {
            $this->htmlPricePublic = $public;
            $this->sodimacTypedPricesPresent = true;
        }
        if ($cmr !== null) {
            $this->htmlPriceCmr = $cmr;
            $this->sodimacTypedPricesPresent = true;
            $this->sodimacTypedCmrMissing = false;
        }
        if ($this->sodimacTypedPricesPresent && $cmr === null) {
            $this->htmlPriceCmr = null;
            $this->sodimacTypedCmrMissing = true;
        }

        if ($this->sodimacTypedPricesPresent) {
            $this->sodimacNextDataPricesPresent = true;
            $this->debugMatches[] = [
                'pattern' => '__NEXT_DATA__ prices',
                'raw' => 'variant:' . ($variantId ?? 'n/a'),
                'value' => $public ?? $cmr,
                'type' => 'sodimac_next_data',
            ];
        }
    }


    private function priceMatchesAny(?float $value, array $candidates, float $tolerance = 0.01): bool
    {
        if ($value === null) {
            return false;
        }
        foreach ($candidates as $candidate) {
            if ($candidate !== null && abs($value - $candidate) <= $tolerance) {
                return true;
            }
        }
        return false;
    }

    private function extractOechsleProductIdFromHtml(string $html): ?string
    {
        if (preg_match('/"productId"\\s*:\\s*([0-9]+)/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/productId"\\s*value="([0-9]+)"/', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractOechsleProductIdFromUrl(string $url): ?string
    {
        if (preg_match('/-([0-9]+)\\/p\\/?$/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/\\/([0-9]+)\\/p\\/?$/', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    private function fetchOechsleCardPriceFromApi(string $productId): ?float
    {
        $endpoint = 'https://api.retailrocket.net/api/1.0/partner/5e6260df97a5251a10daf30d/items/';
        $url = $endpoint . '?itemsIds=' . urlencode($productId) . '&stock=&format=json';

        try {
            $response = $this->client->get($url, [
                'timeout' => 12,
                'http_errors' => false,
            ]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $data = json_decode((string) $response->getBody(), true);

            if (!is_array($data)) {
                return null;
            }
            $items = null;
            if (isset($data['value'])) {
                $items = $data['value'];
            } elseif (is_array($data) && array_is_list($data)) {
                $items = $data;
            }
            if (!is_array($items) || empty($items)) {
                return null;
            }
            $item = $items[0];
            $params = $item['Params'] ?? ($item['params'] ?? null);
            $card = null;
            if (is_array($params)) {
                $card = $params['tarjeta'] ?? ($params['Tarjeta'] ?? null);
            }
            if ($card === null) {
                return null;
            }
            return $this->toNumber((string) $card);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

