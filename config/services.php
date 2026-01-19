<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'supermarket_comparator' => [
        'plaza_vea_base_url' => env('SMC_PLAZA_VEA_BASE_URL', 'https://www.plazavea.com.pe'),
        'metro_base_url' => env('SMC_METRO_BASE_URL', 'https://www.metro.pe'),
        'wong_base_url' => env('SMC_WONG_BASE_URL', 'https://www.wong.pe'),
        'tottus_base_url' => env('SMC_TOTTUS_BASE_URL', 'https://www.tottus.com.pe'),
        // Ej: "tottus" para ejecutar solo esa tienda temporalmente.
        'only_store' => env('SMC_ONLY_STORE', null),

        // Catálogo de marcas (sin BD) - actualización en vivo desde scraping.
        'brand_catalog_live_update' => env('SMC_BRAND_CATALOG_LIVE_UPDATE', true),
        'brand_catalog_max' => (int) env('SMC_BRAND_CATALOG_MAX', 5000),
        'brand_catalog_max_examples' => (int) env('SMC_BRAND_CATALOG_MAX_EXAMPLES', 5),
        'brand_catalog_detect_limit' => (int) env('SMC_BRAND_CATALOG_DETECT_LIMIT', 800),

        // Protección anti-bloqueo (rate limit + circuit breaker) a nivel servidor.
        'rate_limit_per_minute' => (int) env('SMC_RATE_LIMIT_PER_MINUTE', 12),
        'circuit_failure_threshold' => (int) env('SMC_CIRCUIT_FAILURE_THRESHOLD', 3),
        'circuit_cooldown_minutes' => (int) env('SMC_CIRCUIT_COOLDOWN_MINUTES', 10),
    ],
];
