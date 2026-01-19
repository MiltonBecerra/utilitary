<?php

namespace App\Modules\Core\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class MercadoPagoService
{
    public function createPreference(array $payload): array
    {
        $client = new Client([
            'base_uri' => 'https://api.mercadopago.com',
            'timeout' => 15,
        ]);

        $response = $client->post('/checkout/preferences', [
            'headers' => [
                'Authorization' => 'Bearer ' . config('mercadopago.access_token'),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $data = json_decode((string) $response->getBody(), true);
        return is_array($data) ? $data : [];
    }

    public function getPayment(string $paymentId): array
    {
        $client = new Client([
            'base_uri' => 'https://api.mercadopago.com',
            'timeout' => 15,
        ]);

        $response = $client->get('/v1/payments/' . rawurlencode($paymentId), [
            'headers' => [
                'Authorization' => 'Bearer ' . config('mercadopago.access_token'),
                'Content-Type' => 'application/json',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        return is_array($data) ? $data : [];
    }

    public function buildExternalReference(): string
    {
        return (string) Str::uuid();
    }
}
