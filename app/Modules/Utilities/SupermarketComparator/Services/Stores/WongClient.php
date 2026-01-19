<?php

namespace App\Modules\Utilities\SupermarketComparator\Services\Stores;

use App\Traits\BrowserSimulationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WongClient implements StoreClientInterface, AsyncStoreClientInterface
{
    use BrowserSimulationTrait;

    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 12,
            'http_errors' => false,
            'verify' => false,
        ]);
    }

    public function storeCode(): string
    {
        return 'wong';
    }

    public function storeName(): string
    {
        return 'Wong';
    }

    public function searchWide(string $query, string $location = ''): array
    {
        return $this->searchWideAsync($query, $location)->wait();
    }

    public function searchWideAsync(string $query, string $location = ''): PromiseInterface
    {
        $baseUrl = rtrim(config('services.supermarket_comparator.wong_base_url', 'https://www.wong.pe'), '/');
        $url = $baseUrl . '/api/io/_v/api/intelligent-search/product_search?query=' . urlencode($query) . '&page=1&count=20';

        $requestOptions = $this->getSimulatedOptions($url, 'wong_search');
        $requestOptions['headers']['Origin'] = $baseUrl;
        $requestOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
        $requestOptions['headers']['Accept'] = 'application/json,text/plain,*/*';

        return $this->client->getAsync($url, $requestOptions)->then(function ($response) use ($query, $baseUrl, $url) {
            $status = $response->getStatusCode();
            if ($status !== 200) {
                $fallbackUrl = $baseUrl . '/api/catalog_system/pub/products/search/?ft=' . urlencode($query) . '&_from=0&_to=19';

                $fallbackOptions = $this->getSimulatedOptions($fallbackUrl, 'wong_search_fallback');
                $fallbackOptions['headers']['Origin'] = $baseUrl;
                $fallbackOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
                $fallbackOptions['headers']['Accept'] = 'application/json,text/plain,*/*';

                return $this->client->getAsync($fallbackUrl, $fallbackOptions)->then(function ($fallbackResponse) use ($fallbackUrl, $baseUrl, $query) {
                    return $this->parseResponse($fallbackResponse, $fallbackUrl, $baseUrl, $query);
                });
            }

            return $this->parseResponse($response, $url, $baseUrl, $query);
        });
    }

    private function parseResponse($response, string $url, string $baseUrl, string $query): array
    {
        $status = $response->getStatusCode();
        if ($status !== 200) {
            $body = (string) $response->getBody();
            $snippet = trim(substr(preg_replace('/\\s+/', ' ', $body) ?? $body, 0, 140));
            throw new \RuntimeException('Wong: bÆ\'®squeda no disponible (HTTP ' . $status . '). ' . ($snippet ? ('Respuesta: ' . $snippet) : ''));
        }

        $body = (string) $response->getBody();
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Wong: respuesta invÆ\'ñlida (no JSON).');
        }

        $products = $payload['products'] ?? $payload;
        if (!is_array($products)) {
            throw new \RuntimeException('Wong: respuesta invÆ\'ñlida (sin products).');
        }

        $items = $this->buildItemsFromProducts($products, $baseUrl);
        $this->applySmDigitalPrimePricesIfEnabled($query, $items);
        return $items;
    }

    private function buildItemsFromProducts(array $products, string $baseUrl): array
    {
        $items = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $title = (string) ($product['productName'] ?? $product['productTitle'] ?? '');
            if ($title === '') {
                continue;
            }

            $brand = $product['brand'] ?? ($product['brandName'] ?? null);
            $link = $product['link'] ?? null;
            $sizeText = null;
            $variant = null;

            $specGroups = $product['specificationGroups'] ?? null;
            if (is_array($specGroups)) {
                foreach ($specGroups as $group) {
                    if (!is_array($group) || !is_array($group['specifications'] ?? null)) {
                        continue;
                    }
                    foreach ($group['specifications'] as $spec) {
                        if (!is_array($spec)) {
                            continue;
                        }
                        $name = mb_strtolower((string) ($spec['name'] ?? $spec['originalName'] ?? ''));
                        $values = $spec['values'] ?? null;
                        $value0 = is_array($values) ? ($values[0] ?? null) : null;
                        $value0 = is_string($value0) ? trim($value0) : null;
                        if (!$value0) {
                            continue;
                        }
                        if ($sizeText === null && str_contains($name, 'contenido neto')) {
                            $sizeText = $value0;
                        }
                        if ($variant === null && (str_contains($name, 'sabor') || str_contains($name, 'variedad') || str_contains($name, 'contenido de grasa'))) {
                            $variant = $value0;
                        }
                    }
                }
            }

            $firstItem = is_array($product['items'] ?? null) ? ($product['items'][0] ?? null) : null;
            $skuId = is_array($firstItem) && isset($firstItem['itemId']) ? (string) $firstItem['itemId'] : null;
            $imageUrl = null;
            if (is_array($firstItem) && is_array($firstItem['images'] ?? null)) {
                $img0 = $firstItem['images'][0] ?? null;
                if (is_array($img0) && isset($img0['imageUrl']) && is_string($img0['imageUrl'])) {
                    $imageUrl = $img0['imageUrl'];
                }
            }
            $firstSeller = is_array($firstItem['sellers'] ?? null) ? ($firstItem['sellers'][0] ?? null) : null;
            $offer = is_array($firstSeller['commertialOffer'] ?? null) ? ($firstSeller['commertialOffer'] ?? null) : null;

            $price = isset($offer['Price']) ? (float) $offer['Price'] : null;
            $listPrice = isset($offer['ListPrice']) ? (float) $offer['ListPrice'] : null;
            [$cardPrice, $cardLabel] = is_array($offer) ? $this->extractCardPriceFromOffer($offer) : [null, null];
            if ($cardPrice === null) {
                [$cardPrice, $cardLabel] = $this->extractCardPriceFromProduct($product);
            }
            $availableQty = isset($offer['AvailableQuantity']) ? (float) $offer['AvailableQuantity'] : null;

            $inStock = $availableQty === null ? true : ($availableQty > 0);
            $promoText = null;
            if ($listPrice !== null && $price !== null && $listPrice > $price) {
                $promoText = 'Antes S/ ' . number_format($listPrice, 2);
            }
            if (is_array($offer) && is_array($offer['teasers'] ?? null) && !empty($offer['teasers'])) {
                $names = [];
                foreach ($offer['teasers'] as $t) {
                    if (is_array($t) && isset($t['name']) && is_string($t['name'])) {
                        $names[] = trim($t['name']);
                    }
                }
                $names = array_values(array_filter(array_unique($names)));
                if (!empty($names)) {
                    $promoText = trim(($promoText ? ($promoText . ' †®% ') : '') . implode(', ', $names));
                }
            }

            $items[] = [
                'title' => $title,
                'brand' => is_string($brand) ? $brand : null,
                'variant' => $variant,
                'audience' => null,
                'price' => $price,
                'card_price' => $cardPrice,
                'card_label' => $cardLabel,
                'promo_text' => $promoText,
                'in_stock' => $inStock,
                'url' => is_string($link) && Str::startsWith($link, 'http') ? $link : ($link ? $baseUrl . $link : null),
                'image_url' => $imageUrl,
                'size_text' => $sizeText,
                'sku_id' => $skuId,
            ];
        }

        return $items;
    }

    private function applySmDigitalPrimePricesIfEnabled(string $query, array &$items): void
    {
        if (!env('SMC_WONG_SMDIGITAL_PRIME')) {
            return;
        }

        $apiKey = (string) env('SMDIGITAL_API_KEY', '');
        if ($apiKey === '') {
            Log::warning('wong_smdigital_missing_api_key');
            return;
        }

        $skuIds = [];
        foreach ($items as $it) {
            if (is_array($it) && isset($it['sku_id']) && is_string($it['sku_id']) && $it['sku_id'] !== '') {
                $skuIds[] = $it['sku_id'];
            }
        }
        $skuIds = array_values(array_unique($skuIds));
        if (empty($skuIds)) {
            return;
        }

        $result = $this->fetchPrimePricesFromSmDigital($query, $skuIds, $apiKey);
        $priceBySku = is_array($result['prices_by_sku'] ?? null) ? $result['prices_by_sku'] : [];

        if (empty($priceBySku)) {
            return;
        }

        foreach ($items as &$it) {
            if (!is_array($it)) {
                continue;
            }
            $sku = $it['sku_id'] ?? null;
            if (!is_string($sku) || $sku === '') {
                continue;
            }
            if (!isset($priceBySku[$sku])) {
                continue;
            }
            $prime = $priceBySku[$sku];
            if (!is_numeric($prime)) {
                continue;
            }
            $it['card_price'] = (float) $prime;
            $it['card_label'] = 'Prime';
        }
        unset($it);
    }

    private function fetchPrimePricesFromSmDigital(string $query, array $skuIds, string $apiKey): array
    {
        $url = 'https://api.smdigital.pe/v1/pe/masters/service-prices-prime-mdw/searchPrice';

        $templateJson = (string) env('SMC_WONG_SMDIGITAL_BODY_JSON', '');
        $template = null;
        if ($templateJson !== '') {
            $template = json_decode($templateJson, true);
        }
        if (!is_array($template)) {
            $template = [
                'skuIdList' => '{{sku_ids}}',
                'account' => '{{account}}',
            ];
        }

        $body = $this->applyTemplate($template, [
            'query' => $query,
            'sku_ids' => $skuIds,
            'account' => (string) env('SMC_WONG_SMDIGITAL_ACCOUNT', 'wongio'),
        ]);

        try {
            $options = $this->getSimulatedOptions($url, 'wong_smdigital_prime');
            $options['headers']['Accept'] = 'application/json';
            $options['headers']['Content-Type'] = 'application/json';
            $options['headers']['Origin'] = 'https://www.wong.pe';
            $options['headers']['Referer'] = 'https://www.wong.pe/';
            $options['headers']['x-api-key'] = $apiKey;
            $options['json'] = $body;

            $resp = $this->client->post($url, $options);
            $status = $resp->getStatusCode();
            $raw = (string) $resp->getBody();
            $decoded = json_decode($raw, true);
            $response = is_array($decoded) ? $decoded : $raw;

            return [
                'status' => $status,
                'response' => $response,
                'prices_by_sku' => $this->extractPricesBySkuFromSmDigitalResponse($response),
            ];
        } catch (\Throwable $e) {
            return ['status' => null, 'response' => null, 'prices_by_sku' => []];
        }
    }

    private function applyTemplate($template, array $ctx)
    {
        if (is_string($template)) {
            if ($template === '{{query}}') {
                return (string) ($ctx['query'] ?? '');
            }
            if ($template === '{{sku_ids}}') {
                return (array) ($ctx['sku_ids'] ?? []);
            }
            if ($template === '{{account}}') {
                return (string) ($ctx['account'] ?? '');
            }
            return str_replace(
                ['{{query}}', '{{account}}'],
                [(string) ($ctx['query'] ?? ''), (string) ($ctx['account'] ?? '')],
                $template
            );
        }

        if (is_array($template)) {
            $out = [];
            foreach ($template as $k => $v) {
                $out[$k] = $this->applyTemplate($v, $ctx);
            }
            return $out;
        }

        return $template;
    }

    private function extractPricesBySkuFromSmDigitalResponse($response): array
    {
        if (!is_array($response)) {
            return [];
        }

        $candidateLists = [];
        $keys = array_keys($response);
        if ($keys === range(0, count($keys) - 1)) {
            $candidateLists[] = $response;
        }
        foreach (['data', 'items', 'prices', 'results'] as $k) {
            if (isset($response[$k]) && is_array($response[$k])) {
                $candidateLists[] = $response[$k];
            }
        }
        if (isset($response['message']) && is_array($response['message'])) {
            $candidateLists[] = $response['message'];
        }

        $out = [];
        foreach ($candidateLists as $list) {
            foreach ($list as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $sku = null;
                foreach (['sku', 'skuId', 'skuID', 'itemId', 'id'] as $k) {
                    if (isset($row[$k]) && (is_string($row[$k]) || is_numeric($row[$k]))) {
                        $sku = (string) $row[$k];
                        break;
                    }
                }
                if ($sku === null || $sku === '') {
                    continue;
                }

                $price = null;
                foreach (['primePrice', 'prime_price', 'pricePrime', 'cardPrice', 'price'] as $k) {
                    if (!isset($row[$k])) {
                        continue;
                    }
                    $v = $row[$k];
                    if (is_numeric($v)) {
                        $price = (float) $v;
                        break;
                    }
                    if (is_string($v)) {
                        $vv = str_replace([',', ' '], ['.', ''], trim($v));
                        if (is_numeric($vv)) {
                            $price = (float) $vv;
                            break;
                        }
                    }
                }
                if ($price === null || $price <= 0) {
                    continue;
                }

                $out[$sku] = $price;
            }
        }

        return $out;
    }

    private function extractCardPriceFromOffer(array $offer): array
    {
        $candidates = [];

        $basePrice = isset($offer['Price']) && is_numeric($offer['Price']) ? (float) $offer['Price'] : null;

        if ($basePrice !== null && is_array($offer['teasers'] ?? null) && !empty($offer['teasers'])) {
            foreach ($offer['teasers'] as $teaser) {
                if (!is_array($teaser)) {
                    continue;
                }

                $name = isset($teaser['name']) && is_string($teaser['name']) ? $teaser['name'] : '';
                $lname = strtolower($name);

                if ($name === '' || !preg_match('/(tcenco|cencosud|tarjeta|card|cmr|oh)/i', $name)) {
                    continue;
                }

                $effects = is_array($teaser['effects'] ?? null) ? $teaser['effects'] : null;
                $params = is_array($effects['parameters'] ?? null) ? $effects['parameters'] : null;
                if (!is_array($params)) {
                    continue;
                }

                $percent = null;
                foreach ($params as $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    if (($p['name'] ?? null) !== 'PercentualDiscount') {
                        continue;
                    }
                    $v = $p['value'] ?? null;
                    if (is_numeric($v)) {
                        $percent = (float) $v;
                        break;
                    }
                    if (is_string($v)) {
                        $vv = str_replace([',', ' '], ['.', ''], trim($v));
                        if (is_numeric($vv)) {
                            $percent = (float) $vv;
                            break;
                        }
                    }
                }

                if ($percent === null || $percent <= 0 || $percent >= 100) {
                    continue;
                }

                $candidate = $basePrice * (1.0 - ($percent / 100.0));
                if ($candidate <= 0 || $candidate > 50000) {
                    continue;
                }

                $label = 'Tarjeta';
                if (str_contains($lname, 'prime')) {
                    $label = 'Prime';
                } elseif (str_contains($lname, 'tcenco') || str_contains($lname, 'cencosud')) {
                    $label = 'Cencosud';
                } elseif (str_contains($lname, 'cmr')) {
                    $label = 'CMR';
                } elseif (str_contains($lname, 'oh')) {
                    $label = 'OH!';
                }

                $candidates[] = ['price' => $candidate, 'label' => $label];
            }
        }

        $priceTags = $offer['PriceTags'] ?? ($offer['priceTags'] ?? null);
        if ($basePrice !== null && is_array($priceTags)) {
            foreach ($priceTags as $tag) {
                if (!is_array($tag)) {
                    continue;
                }
                $name = isset($tag['name']) && is_string($tag['name']) ? $tag['name'] : (isset($tag['Name']) && is_string($tag['Name']) ? $tag['Name'] : '');
                $value = $tag['value'] ?? ($tag['Value'] ?? null);

                if ($name === '' || !preg_match('/(cmr|oh|prime|tarjeta|cencosud|card)/i', $name)) {
                    continue;
                }
                if (!is_numeric($value)) {
                    continue;
                }

                $delta = (float) $value;
                if (abs($delta) > 1000 && $basePrice < 1000) {
                    $delta = $delta / 100.0;
                }

                $candidate = $basePrice + $delta;
                if ($candidate <= 0 || $candidate > 50000) {
                    continue;
                }

                $label = 'Tarjeta';
                $lname = strtolower($name);
                if (str_contains($lname, 'prime')) {
                    $label = 'Prime';
                } elseif (str_contains($lname, 'cmr')) {
                    $label = 'CMR';
                } elseif (str_contains($lname, 'oh')) {
                    $label = 'OH!';
                } elseif (str_contains($lname, 'cencosud')) {
                    $label = 'Cencosud';
                }

                $candidates[] = ['price' => $candidate, 'label' => $label];
            }
        }

        $walk = function (array $node, int $depth) use (&$walk, &$candidates): void {
            if ($depth > 4) {
                return;
            }

            foreach ($node as $k => $v) {
                $key = is_string($k) ? strtolower($k) : '';

                if (is_array($v)) {
                    $walk($v, $depth + 1);
                    continue;
                }

                if ($key === '' || (!is_numeric($v) && !is_string($v))) {
                    continue;
                }

                if (!preg_match('/(cmr|oh|prime|tarjeta|card)/i', $key)) {
                    continue;
                }

                $price = is_numeric($v) ? (float) $v : null;
                if ($price === null && is_string($v)) {
                    $vv = str_replace([',', ' '], ['.', ''], $v);
                    $price = is_numeric($vv) ? (float) $vv : null;
                }

                if ($price === null || $price <= 0) {
                    continue;
                }

                $label = 'Tarjeta';
                if (str_contains($key, 'cmr')) {
                    $label = 'CMR';
                } elseif (str_contains($key, 'oh')) {
                    $label = 'OH!';
                } elseif (str_contains($key, 'prime')) {
                    $label = 'Prime';
                }

                $candidates[] = ['price' => $price, 'label' => $label];
            }
        };

        $walk($offer, 0);

        if (empty($candidates)) {
            return [null, null];
        }

        usort($candidates, fn ($a, $b) => ($a['price'] <=> $b['price']));
        return [(float) $candidates[0]['price'], (string) $candidates[0]['label']];
    }

    private function extractCardPriceFromProduct(array $product): array
    {
        $candidates = [];
        $matchedKeys = [];

        $walk = function ($node, int $depth, string $path) use (&$walk, &$candidates, &$matchedKeys): void {
            if ($depth > 6) {
                return;
            }
            if (!is_array($node)) {
                return;
            }
            foreach ($node as $k => $v) {
                $key = is_string($k) ? strtolower($k) : '';
                $p = $path . (is_string($k) ? ('.' . $k) : '');

                if (is_array($v)) {
                    $walk($v, $depth + 1, $p);
                    continue;
                }

                if ($key === '' || (!is_numeric($v) && !is_string($v))) {
                    continue;
                }

                if (!preg_match('/(cmr|oh|prime|tarjeta|card)/i', $key)) {
                    continue;
                }

                $matchedKeys[] = $p;

                $price = is_numeric($v) ? (float) $v : null;
                if ($price === null && is_string($v)) {
                    $vv = str_replace([',', ' '], ['.', ''], $v);
                    $price = is_numeric($vv) ? (float) $vv : null;
                }

                if ($price === null || $price <= 0) {
                    continue;
                }

                $label = 'Tarjeta';
                if (str_contains($key, 'prime')) {
                    $label = 'Prime';
                } elseif (str_contains($key, 'cmr')) {
                    $label = 'CMR';
                } elseif (str_contains($key, 'oh')) {
                    $label = 'OH!';
                }

                if ($price > 50000) {
                    continue;
                }

                $candidates[] = ['price' => $price, 'label' => $label];
            }
        };

        $walk($product, 0, 'product');

        if ((config('app.debug') || env('SMC_DEBUG_WONG')) && !empty($matchedKeys)) {
            Log::info('wong_card_price_keys_found', ['keys' => array_slice(array_values(array_unique($matchedKeys)), 0, 12)]);
        }

        if (empty($candidates)) {
            return [null, null];
        }

        usort($candidates, fn ($a, $b) => ($a['price'] <=> $b['price']));
        return [(float) $candidates[0]['price'], (string) $candidates[0]['label']];
    }
}

