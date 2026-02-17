<?php

namespace App\Modules\Core\Services;

use App\Models\WhatsAppContact;

class WhatsAppRegistryService
{
    public function normalizePhone(?string $phone): ?string
    {
        $value = trim((string) $phone);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', '', $value);
        $value = preg_replace('/[^\d\+]/', '', $value);

        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }

        if (!str_starts_with($value, '+')) {
            $value = '+' . $value;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '') {
            return null;
        }

        return '+' . $digits;
    }

    public function registerIfFirst(
        ?string $rawPhone,
        ?int $userId,
        ?string $guestId,
        string $source
    ): array {
        $normalized = $this->normalizePhone($rawPhone);
        if (!$normalized) {
            return [
                'is_first' => false,
                'normalized_phone' => null,
            ];
        }

        $existing = WhatsAppContact::where('normalized_phone', $normalized)->first();
        if ($existing) {
            return [
                'is_first' => false,
                'normalized_phone' => $normalized,
            ];
        }

        WhatsAppContact::create([
            'normalized_phone' => $normalized,
            'raw_phone' => $rawPhone,
            'user_id' => $userId,
            'guest_id' => $guestId,
            'first_source' => $source,
            'first_prompted_at' => now(),
        ]);

        return [
            'is_first' => true,
            'normalized_phone' => $normalized,
        ];
    }

    public function getCompanyNumber(): string
    {
        return (string) config('services.whatsapp.company_number', '+51999999999');
    }

    public function getCompanyMessage(string $normalizedPhone): string
    {
        $template = (string) config(
            'services.whatsapp.first_contact_message',
            'Hola, quiero registrar este numero para recibir alertas de Utilitary: :phone'
        );

        return str_replace(':phone', $normalizedPhone, $template);
    }

    public function getCompanyClickToChatUrl(string $message = ''): string
    {
        $company = preg_replace('/\D+/', '', $this->getCompanyNumber());
        $base = 'https://wa.me/' . $company;

        if ($message === '') {
            return $base;
        }

        return $base . '?text=' . rawurlencode($message);
    }
}
