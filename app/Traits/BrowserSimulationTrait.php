<?php

namespace App\Traits;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait BrowserSimulationTrait
{
    /**
     * Generates a consistent set of browser headers including randomized User-Agent,
     * Referer, Accept-Language, and correct Sec-Ch-Ua-Platform.
     */
    protected function getBrowserHeaders(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        
        // Pick random referer
        $refererTpl = Arr::random(config('scraper.referers', ['https://www.google.com/']));
        $referer = str_replace('{HOST}', $host ? ('https://' . $host . '/') : '', $refererTpl);

        // Pick random User-Agent
        $ua = Arr::random(config('scraper.user_agents', [config('scraper.user_agent')]));
        
        // Derive platform from UA
        $platform = '"Windows"'; // Default
        if (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) {
            $platform = '"macOS"';
        } elseif (str_contains($ua, 'Linux') || str_contains($ua, 'X11')) {
            $platform = '"Linux"';
        } elseif (str_contains($ua, 'Android')) {
            $platform = '"Android"';
        }

        $headers = [
            'User-Agent' => $ua,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => Arr::random(config('scraper.languages', ['es-PE,es;q=0.9,en-US;q=0.8,en;q=0.7'])),
            'Referer' => $referer,
            'Upgrade-Insecure-Requests' => '1',
            'Cache-Control' => 'max-age=0',
        ];

        // Only add Sec-Ch-Ua headers for Chromium browsers (Chrome/Edge)
        if (str_contains($ua, 'Chrome') || str_contains($ua, 'Edg')) {
            $headers['Sec-Ch-Ua'] = '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"';
            $headers['Sec-Ch-Ua-Mobile'] = '?0';
            $headers['Sec-Ch-Ua-Platform'] = $platform;
            $headers['Sec-Fetch-Dest'] = 'document';
            $headers['Sec-Fetch-Mode'] = 'navigate';
            $headers['Sec-Fetch-Site'] = 'same-origin';
            $headers['Sec-Fetch-User'] = '?1';
        }

        return $headers;
    }

    /**
     * Helper to get standard simulated options (Headers + Log + Fresh Cookies).
     * This ensures each request looks like a unique session.
     */
    protected function getSimulatedOptions(string $url, string $logContext): array
    {
        $headers = $this->getBrowserHeaders($url);
        $jar = new CookieJar();
        $this->logBrowserIdentity($logContext, $url, $headers, $jar);

        return [
            'headers' => $headers,
            'cookies' => $jar, // Force fresh cookie jar (User-Agent changed -> New Session)
        ];
    }

    /**
     * Logs the simulated browser identity for debugging purposes.
     */
    protected function logBrowserIdentity(string $context, string $url, array $headers, ?CookieJar $jar = null): void
    {
        Log::info("{$context}_identity", [
            'url' => $url,
            'ua' => $headers['User-Agent'] ?? 'unknown',
            'referer' => $headers['Referer'] ?? 'unknown',
            'platform' => $headers['Sec-Ch-Ua-Platform'] ?? 'N/A',
            'cookies' => $jar ? 'Fresh Session (0 cookies)' : 'Persisted (Potentially Dirty)',
        ]);
    }
}
