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
            $sellerOffer = $this->selectSellerOffer(is_array($firstItem['sellers'] ?? null) ? $firstItem['sellers'] : []);
            $firstSeller = $sellerOffer['seller'] ?? null;
            $offer = $sellerOffer['offer'] ?? null;

            $price = isset($offer['Price']) ? (float) $offer['Price'] : null;
            $listPrice = isset($offer['ListPrice']) ? (float) $offer['ListPrice'] : null;
            $availableQty = isset($offer['AvailableQuantity']) ? (float) $offer['AvailableQuantity'] : null;
            $cardPrice = null;
            $cardLabel = null;

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

            if (is_array($offer)) {
                [$cardPrice, $cardLabel] = $this->extractCardPriceFromOffer($offer);
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
            ];
        }

        return $items;
    }

    /**
     * @param array $sellers
     * @return array{seller: array|null, offer: array|null}
     */
    private function selectSellerOffer(array $sellers): array
    {
        $fallbackSeller = null;
        $fallbackOffer = null;
        $bestSeller = null;
        $bestOffer = null;

        foreach ($sellers as $seller) {
            if (!is_array($seller)) {
                continue;
            }
            $offer = is_array($seller['commertialOffer'] ?? null) ? $seller['commertialOffer'] : null;
            if ($fallbackSeller === null) {
                $fallbackSeller = $seller;
                $fallbackOffer = $offer;
            }
            if (!is_array($offer)) {
                continue;
            }
            $price = $this->parseNumeric($offer['Price'] ?? null);
            $available = $this->parseNumeric($offer['AvailableQuantity'] ?? null);
            if ($price !== null && $price > 0 && ($available === null || $available > 0)) {
                $bestSeller = $seller;
                $bestOffer = $offer;
                break;
            }
            if ($bestOffer === null && $price !== null && $price > 0) {
                $bestSeller = $seller;
                $bestOffer = $offer;
            }
        }

        if ($bestOffer !== null) {
            return ['seller' => $bestSeller, 'offer' => $bestOffer];
        }

        return ['seller' => $fallbackSeller, 'offer' => $fallbackOffer];
    }

    private function extractCardPriceFromOffer(array $offer): array
    {
        $candidates = [];
        $basePrice = $this->parseNumeric($offer['Price'] ?? null);
        $priceWithoutDiscount = $this->parseNumeric($offer['PriceWithoutDiscount'] ?? null);

        if ($basePrice !== null && is_array($offer['teasers'] ?? null) && !empty($offer['teasers'])) {
            foreach ($offer['teasers'] as $teaser) {
                if (!is_array($teaser)) {
                    continue;
                }

                $name = isset($teaser['name']) && is_string($teaser['name']) ? $teaser['name'] : '';
                if ($name === '' || !preg_match('/(tcenco|cencosud|tarjeta|card|cmr|oh)/i', $name)) {
                    continue;
                }

                $effects = is_array($teaser['effects'] ?? null) ? $teaser['effects'] : null;
                $params = is_array($effects['parameters'] ?? null) ? $effects['parameters'] : null;
                if (!is_array($params)) {
                    continue;
                }

                $percent = null;
                $promoDiscount = null;
                $directPrice = null;
                foreach ($params as $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $paramName = $p['name'] ?? null;
                    $value = $p['value'] ?? null;
                    if ($paramName === 'PercentualDiscount') {
                        $percent = $this->parseNumeric($value);
                    } elseif ($paramName === 'PromotionalPriceTableItemsDiscount') {
                        $promoDiscount = $this->parseNumeric($value);
                    }
                }

                if ($percent === null && $promoDiscount === null) {
                    $directPrice = $this->extractDirectPriceFromName($name);
                }

                if ($promoDiscount !== null && ($priceWithoutDiscount ?? $basePrice) !== null) {
                    $source = $priceWithoutDiscount ?? $basePrice;
                    $candidate = $source - $promoDiscount;
                    if ($candidate > 0 && $candidate < 50000) {
                        $candidates[] = ['price' => $candidate, 'label' => $this->extractCardLabel($name)];
                        continue;
                    }
                }

                if ($percent !== null && $basePrice !== null && $percent > 0 && $percent < 100) {
                    $candidate = $basePrice * (1.0 - ($percent / 100.0));
                    if ($candidate > 0 && $candidate < 50000) {
                        $candidates[] = ['price' => $candidate, 'label' => $this->extractCardLabel($name)];
                    }
                }

                if ($directPrice !== null && $basePrice !== null) {
                    if ($directPrice > 0 && $directPrice < $basePrice) {
                        $candidates[] = ['price' => $directPrice, 'label' => $this->extractCardLabel($name)];
                    }
                }
            }
        }

        if (empty($candidates)) {
            return [null, null];
        }

        usort($candidates, fn ($a, $b) => ($a['price'] <=> $b['price']));
        return [(float) $candidates[0]['price'], (string) $candidates[0]['label']];
    }

    private function extractDirectPriceFromName(string $name): ?float
    {
        $lower = mb_strtolower($name);
        if (!preg_match('/\ba\s*(\d+(?:[\.,]\d{1,2})?)\b/', $lower, $matches)) {
            return null;
        }
        return $this->parseNumeric($matches[1]);
    }

    private function extractCardLabel(string $name): string
    {
        $lname = mb_strtolower($name);
        if (str_contains($lname, 'cencosud') || str_contains($lname, 'tcenco')) {
            return 'Cencosud';
        }
        if (str_contains($lname, 'cmr')) {
            return 'CMR';
        }
        if (str_contains($lname, 'oh')) {
            return 'OH!';
        }
        if (str_contains($lname, 'tarjeta')) {
            return 'Tarjeta';
        }
        return 'Tarjeta';
    }

    private function parseNumeric($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $clean = trim(str_replace([',', ' '], ['.', ''], $value));
            if (is_numeric($clean)) {
                return (float) $clean;
            }
        }
        return null;
    }
}

