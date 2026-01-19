<?php

namespace App\Modules\Utilities\OfferAlerts\Services;

use Illuminate\Support\Facades\Log;

class ProxyRotationService
{
    protected array $proxies = [];
    protected bool $enabled = false;

    public function __construct()
    {
        $this->enabled = config('scraper.proxy_rotation.enabled', false);
        $this->proxies = config('scraper.proxy_rotation.proxies', []);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->proxies);
    }

    public function getRandomProxy(): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $proxy = $this->proxies[array_rand($this->proxies)];
            // Basic validation or formatting could happen here if needed
            return $proxy;
        } catch (\Throwable $e) {
            Log::warning('ProxyRotationService: Failed to get random proxy', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getProxyList(): array
    {
        return $this->proxies;
    }
}

