<?php

namespace App\Modules\Core\Services;

use App\Models\ExchangeRate;
use App\Models\ExchangeSource;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class ScrapingService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout'  => 10.0,
            'headers' => [
                'User-Agent' => \Illuminate\Support\Arr::random(config('scraper.user_agents', ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'])),
            ]
        ]);
    }

    public function scrapeAll()
    {
        $sources = ExchangeSource::where('is_active', true)->get();

        foreach ($sources as $source) {
            try {
                $this->scrapeSource($source);
            } catch (\Exception $e) {
                Log::error("Failed to scrape {$source->name}: " . $e->getMessage());
            }
        }
    }

    public function scrapeSource(ExchangeSource $source)
    {
        try {
            $method = 'scrape' . ucfirst(strtolower($source->name));
            if (method_exists($this, $method)) {
                $this->$method($source);
            } else {
                $this->scrapeGeneric($source);
            }
        } catch (\Exception $e) {
            Log::error("Failed to scrape {$source->name}: " . $e->getMessage());
        }
    }

    protected function scrapeGeneric(ExchangeSource $source)
    {
        $html = $this->fetchHtml($source->url);
        $crawler = new Crawler($html);

        $buyPrice = $this->extractPrice($crawler, $source->selector_buy);
        $sellPrice = $this->extractPrice($crawler, $source->selector_sell);

        $this->saveRate($source, $buyPrice, $sellPrice);
    }

    protected function scrapeKambista(ExchangeSource $source)
    {
        // API pública usada por el frontend
        $buyPrice = null;
        $sellPrice = null;
        try {
            $response = $this->client->request('GET', 'https://api.kambista.com/v1/exchange/calculates', [
                'query' => [
                    'originCurrency' => 'USD',
                    'destinationCurrency' => 'PEN',
                    'amount' => 100, // cualquier monto devuelve la tasa actual
                    'active' => 'S',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Referer' => 'https://kambista.com/',
                    'Origin' => 'https://kambista.com',
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            if (isset($data['tc']['bid']) && isset($data['tc']['ask'])) {
                $buyPrice = $this->normalizePrice($data['tc']['bid']);
                $sellPrice = $this->normalizePrice($data['tc']['ask']);
            }
        } catch (\Exception $e) {
            Log::warning("Kambista API fallback to HTML: " . $e->getMessage());
        }

        if (!$buyPrice || !$sellPrice) {
            $html = $this->fetchHtml($source->url);
            $crawler = new Crawler($html);

            // Prefer explicit DOM selectors that appear en la página pública
            $buyPrice = $buyPrice ?: $this->extractPrice($crawler, '#valcompra');
            $sellPrice = $sellPrice ?: $this->extractPrice($crawler, '#valventa');

            // Fallback: regex que acepta puntos o comas
            if (!$buyPrice || !$sellPrice) {
                preg_match('/compra[^0-9]*([0-9]+[\\.,][0-9]+)/i', $html, $buyMatch);
                preg_match('/venta[^0-9]*([0-9]+[\\.,][0-9]+)/i', $html, $sellMatch);
                $buyPrice = $buyPrice ?: $this->normalizePrice($buyMatch[1] ?? null);
                $sellPrice = $sellPrice ?: $this->normalizePrice($sellMatch[1] ?? null);
            }
        }

        $this->saveRate($source, $buyPrice, $sellPrice);
    }

    protected function scrapeTucambista(ExchangeSource $source)
    {
        $html = $this->fetchHtml($source->url);
        
        // Direct regex on the escaped JSON string in HTML
        $pattern = '/\\\\?"entity\\\\?":\\\\?"tucambista\\\\?",\\\\?"buyExchangeRate\\\\?":(\d+\.\d+),\\\\?"sellExchangeRate\\\\?":(\d+\.\d+)/i';
        
        preg_match($pattern, $html, $matches);

        if (isset($matches[1]) && isset($matches[2])) {
            $this->saveRate($source, $matches[1], $matches[2]);
        } else {
            Log::warning("Tucambista regex failed. Pattern: $pattern");
        }
    }

    protected function scrapeTkambio(ExchangeSource $source)
    {
        try {
            // Tkambio uses a WordPress AJAX endpoint
            $response = $this->client->request('POST', 'https://tkambio.com/wp-admin/admin-ajax.php', [
                'form_params' => [
                    'action' => 'get_exchange_rate'
                ],
                'headers' => [
                    'Accept' => 'application/json, text/javascript, */*; q=0.01',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Origin' => 'https://tkambio.com',
                    'Referer' => 'https://tkambio.com/',
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            if ($data && isset($data['buying_rate']) && isset($data['selling_rate'])) {
                $buyPrice = $data['buying_rate'];
                $sellPrice = $data['selling_rate'];
                
                $this->saveRate($source, $buyPrice, $sellPrice);
            } else {
                Log::warning("Tkambio API returned unexpected format");
            }
            
        } catch (\Exception $e) {
            Log::error("Tkambio scraping failed: " . $e->getMessage());
        }
    }

    protected function saveRate($source, $buy, $sell)
    {
        if ($buy && $sell) {
            ExchangeRate::create([
                'exchange_source_id' => $source->id,
                'buy_price' => $buy,
                'sell_price' => $sell,
                'currency_from' => 'USD',
                'currency_to' => 'PEN',
            ]);
            Log::info("Scraped {$source->name}: Buy {$buy}, Sell {$sell}");
        } else {
            Log::warning("Could not extract prices for {$source->name}");
        }
    }

    protected function fetchHtml($url)
    {
        $response = $this->client->request('GET', $url, [
            'verify' => false,
            'headers' => [
                'User-Agent' => \Illuminate\Support\Arr::random(config('scraper.user_agents', ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'])),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            ]
        ]);
        return (string) $response->getBody();
    }

    protected function extractPrice(Crawler $crawler, $selector)
    {
        try {
            $text = $crawler->filter($selector)->text();
            return $this->normalizePrice($text);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function normalizePrice($value)
    {
        if ($value === null) {
            return null;
        }
        // Permitir comas o puntos, eliminar cualquier otro símbolo
        $clean = preg_replace('/[^0-9.,]/', '', (string) $value);
        if ($clean === '') {
            return null;
        }
        // Si hay coma, reemplazar por punto para float
        $clean = str_replace(',', '.', $clean);
        return (float) $clean;
    }
}
