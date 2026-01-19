<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scraper Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the web scraper, including proxy rotation settings.
    |
    */

    'proxy_rotation' => [
        'enabled' => env('SCRAPER_PROXY_ROTATION_ENABLED', false),
        
        // Proxies extracted from free lists. reliability is not guaranteed.
        // Format: 'ip:port' or 'http://ip:port'
        'proxies' => [
            '46.:80', // Verified working at moment of add
        ],
    ],

    // Global settings
    'timeout' => 12,
    // User Agent Rotation
    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ],
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', // Fallback
    
    // Header Variability
    'referers' => [
        'https://www.google.com/',
        'https://www.google.com.pe/',
        'https://www.bing.com/',
        'https://duckduckgo.com/',
        'https://www.facebook.com/',
        'https://t.co/',
        '{HOST}', // Placeholder for self-referral (e.g., https://www.ripley.com.pe/)
    ],
    
    'languages' => [
        'es-PE,es;q=0.9,en-US;q=0.8,en;q=0.7',
        'es-PE,es;q=0.9',
        'es-ES,es;q=0.9,en;q=0.8',
        'en-US,en;q=0.9,es-PE;q=0.8,es;q=0.7',
    ],
];
