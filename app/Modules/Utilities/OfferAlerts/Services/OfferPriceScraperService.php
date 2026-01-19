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
        try {
            $html = $this->fetchHtml($url);
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
     * Intenta renderizar la página con Puppeteer (JS). Requiere Node y el script scrape_ripley.js en la raíz.
     */
    private function fetchWithPuppeteer(string $url): ?array
    {
        $script = base_path('scrape_ripley.js');
        \Log::info('puppeteer_debug', ['script' => $script, 'exists' => file_exists($script), 'cwd' => getcwd()]);
        if (!file_exists($script)) {
            \Log::warning('puppeteer_script_not_found', ['script' => $script]);
            return null;
        }

        try {
            // "node" no suele estar en el PATH del usuario de Apache/Windows Service
            // Usamos path absoluto comun en Windows o fallback
            $nodePath = 'C:\Program Files\nodejs\node.exe';
            if (!file_exists($nodePath)) {
                 $nodePath = 'node'; // Fallback por si está en otro lado
            }

            $profileBase = storage_path('logs/puppeteer_profiles');
            File::ensureDirectoryExists($profileBase);
            $this->cleanupPuppeteerProfiles($profileBase);
            
            $env = [
                'PUPPETEER_PROFILE_BASE' => $profileBase,
                // Aseguramos System32 en PATH para que Puppeteer pueda usar taskkill en Windows
                'PATH' => (getenv('PATH') ?: '') . ';C:\Windows\System32',
            ];

            $process = new Process([$nodePath, $script, $url], base_path(), $env);
            // Limitar por debajo del max_execution_time habitual (60s)
            $process->setTimeout(45);
            $process->setIdleTimeout(45);
            $process->run();

            if (!$process->isSuccessful()) {
                \Log::error('puppeteer_failed', ['url' => $url, 'error' => $process->getErrorOutput(), 'output' => $process->getOutput()]);
                return null;
            }

            $output = trim($process->getOutput());
            $data = json_decode($output, true);
            if (!is_array($data)) {
                \Log::error('puppeteer_invalid_json', ['output' => $output]);
                return null;
            }

            $publicPrice = isset($data['public_price']) ? (float) $data['public_price'] : (isset($data['price']) ? (float) $data['price'] : null);
            $cardPrice = isset($data['card_price']) ? (float) $data['card_price'] : null;
            $price = $publicPrice ?? $cardPrice;

            return [
                'title' => $data['title'] ?? 'Producto',
                'price' => $price,
                'public_price' => $publicPrice ?? $price,
                'cmr_price' => $cardPrice,
                'image_url' => $data['image'] ?? 'https://via.placeholder.com/320x240.png?text=Producto',
                'store' => 'ripley',
                'url' => $data['url'] ?? $url,
            ];
        } catch (ProcessTimedOutException $e) {
            \Log::error('puppeteer_timeout', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        } catch (\Throwable $e) {
            \Log::error('puppeteer_exception', ['url' => $url, 'error' => $e->getMessage()]);
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
        $publicPrices = $this->gatherPrices($crawler, [
            '.fb-product-cta__prices__price-final',
            '.product-info__price',
            '.product-prices__value',
            '[data-test-id="product-price"]',
            '[itemprop="price"]',
            'meta[property="product:price:amount"]',
        ]);
        $cmrPrices = $this->gatherPrices($crawler, [
            '.product-prices__value--cmr',
            '.fb-product-cta__prices__price-cmr',
            '[data-test-id="cmr-price"]',
            '[data-testid="cmr-price"]',
        ]);

        $offerPrices = $this->extractOfferPrices($data);
        $publicPreferred = $this->firstNonNull([
            $this->maxPrice($offerPrices),
            $publicPrices[0] ?? null,
        ]);
        $cmrPreferred = $cmrPrices[0] ?? (count($offerPrices) > 1 ? $this->minPrice($offerPrices) : null);

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
        $publicPrices = $this->gatherPrices($crawler, [
            '.product-info-price .price',
            '.price-box .price',
            'meta[property="product:price:amount"]',
        ]);
        $cardPrices = $this->gatherPrices($crawler, [
            '.product-info-price .oh-price',
            '.product-info-price .card-price',
            '.product-info-price .credit-price',
            '[data-testid="card-price"]',
        ]);

        $publicPreferred = $publicPrices[0] ?? null;
        $cardPreferred = $cardPrices[0] ?? null;

        $this->htmlPricePublic = $this->htmlPricePublic ?? $publicPreferred;
        $this->htmlPriceCmr = $this->htmlPriceCmr ?? $cardPreferred;

        $title = $this->textFirst($crawler, 'h1', 'Producto Oechsle');
        $image = $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');

        return $this->buildPayload($title, $publicPrices, $image, 'oechsle', $url, $publicPreferred, $publicPrices, $cardPrices);
    }

    private function scrapeSodimac(Crawler $crawler, string $url): array
    {
        $data = $this->parseJsonLd($crawler);
        $publicPrices = $this->gatherPrices($crawler, [
            '.product-prices__value',
            '.fbra_text--product-price',
            'meta[property="product:price:amount"]',
        ]);

        $cardPrices = $this->gatherPrices($crawler, [
            '.product-prices__card',
            '.product-prices__value--cmr',
            '.product-prices__value--unica',
            '[data-testid="card-price"]',
        ]);

        $preferred = $this->firstNonNull([
            $this->toNumber($data['offers']['price'] ?? null),
            $publicPrices[0] ?? null,
        ]);

        $this->htmlPricePublic = $this->htmlPricePublic ?? $preferred;
        $this->htmlPriceCmr = $this->htmlPriceCmr ?? ($cardPrices[0] ?? null);

        $title = $data['name'] ?? $this->textFirst($crawler, 'h1', 'Producto Sodimac');
        $image = $data['image'] ?? $this->imageFirst($crawler, 'meta[property="og:image"]', 'content');

        return $this->buildPayload($title, $publicPrices, $image, 'sodimac', $url, $preferred, $publicPrices, $cardPrices);
    }

    private function scrapePromart(Crawler $crawler, string $url): array
    {
        $data = $this->parseJsonLd($crawler);
        $publicPrices = $this->gatherPrices($crawler, [
            '.product-price',
            '.price-box .price',
            'meta[property="product:price:amount"]',
        ]);

        $cardPrices = $this->gatherPrices($crawler, [
            '.product-price__card',
            '.product-price__oh',
            '.price-oh',
            '.card-price',
            '[data-testid="card-price"]',
        ]);

        $preferred = $this->firstNonNull([
            $this->toNumber($data['offers']['price'] ?? null),
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

        foreach ($patternsCmr as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                if (stripos($m[0], 'jsx-') !== false) {
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
}

