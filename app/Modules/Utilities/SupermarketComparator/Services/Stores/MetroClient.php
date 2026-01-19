<?php

namespace App\Modules\Utilities\SupermarketComparator\Services\Stores;

use App\Traits\BrowserSimulationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Str;

class MetroClient implements StoreClientInterface, AsyncStoreClientInterface
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
        return 'metro';
    }

    public function storeName(): string
    {
        return 'Metro';
    }

    public function searchWide(string $query, string $location = ''): array
    {
        return $this->searchWideAsync($query, $location)->wait();
    }

    public function searchWideAsync(string $query, string $location = ''): PromiseInterface
    {
        $baseUrl = rtrim(config('services.supermarket_comparator.metro_base_url', 'https://www.metro.pe'), '/');
        $url = $baseUrl . '/api/io/_v/api/intelligent-search/product_search?query=' . urlencode($query) . '&page=1&count=20';

        $requestOptions = $this->getSimulatedOptions($url, 'metro_search');
        $requestOptions['headers']['Origin'] = $baseUrl;
        $requestOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
        $requestOptions['headers']['Accept'] = 'application/json,text/plain,*/*';

        return $this->client->getAsync($url, $requestOptions)->then(function ($response) use ($query, $baseUrl, $url) {
            $status = $response->getStatusCode();
            if ($status !== 200) {
                $fallbackUrl = $baseUrl . '/api/catalog_system/pub/products/search/?ft=' . urlencode($query) . '&_from=0&_to=19';

                $fallbackOptions = $this->getSimulatedOptions($fallbackUrl, 'metro_search_fallback');
                $fallbackOptions['headers']['Origin'] = $baseUrl;
                $fallbackOptions['headers']['X-Requested-With'] = 'XMLHttpRequest';
                $fallbackOptions['headers']['Accept'] = 'application/json,text/plain,*/*';

                return $this->client->getAsync($fallbackUrl, $fallbackOptions)->then(function ($fallbackResponse) use ($fallbackUrl, $baseUrl) {
                    return $this->parseResponse($fallbackResponse, $fallbackUrl, $baseUrl);
                });
            }

            return $this->parseResponse($response, $url, $baseUrl);
        });
    }

    private function parseResponse($response, string $url, string $baseUrl): array
    {
        $status = $response->getStatusCode();
        if ($status !== 200) {
            $body = (string) $response->getBody();
            $snippet = trim(substr(preg_replace('/\\s+/', ' ', $body) ?? $body, 0, 140));
            throw new \RuntimeException('Metro: busqueda no disponible (HTTP ' . $status . '). ' . ($snippet ? ('Respuesta: ' . $snippet) : ''));
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Metro: respuesta invalida (no JSON).');
        }

        $products = $payload['products'] ?? $payload;
        if (!is_array($products)) {
            throw new \RuntimeException('Metro: respuesta invalida (sin products).');
        }

        return $this->buildItemsFromProducts($products, $baseUrl);
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
                    $promoText = trim(($promoText ? ($promoText . ' 嫉 ') : '') . implode(', ', $names));
                }
            }

            $items[] = [
                'title' => $title,
                'brand' => is_string($brand) ? $brand : null,
                'variant' => $variant,
                'audience' => null,
                'price' => $price,
                'promo_text' => $promoText,
                'in_stock' => $inStock,
                'url' => is_string($link) && Str::startsWith($link, 'http') ? $link : ($link ? $baseUrl . $link : null),
                'image_url' => $imageUrl,
                'size_text' => $sizeText,
            ];
        }

        return $items;
    }
}

