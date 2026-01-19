<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Utility;
use App\Models\UtilityPayment;
use App\Modules\Core\Services\GuestService;
use App\Modules\Core\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected MercadoPagoService $mp,
        protected GuestService $guestService,
        protected \App\Modules\Core\Services\SubscriptionActivationService $subscriptionActivator,
    ) {
    }

    public function createUtilityPayment(Request $request, string $utility)
    {
        $validated = $request->validate([
            'plan' => 'required|in:basic,pro',
        ]);

        $knownUtilities = [
            'currency-alert' => [
                'name' => 'Alertas de divisas',
                'description' => 'Recibe alertas de tipo de cambio.',
                'icon' => 'fas fa-bell',
            ],
            'offer-alert' => [
                'name' => 'Alertas de ofertas',
                'description' => 'Monitorea precios y recibe alertas cuando bajen.',
                'icon' => 'fas fa-tag',
            ],
            'supermarket-comparator' => [
                'name' => 'Comparador de supermercados',
                'description' => 'Compara precios entre supermercados.',
                'icon' => 'fas fa-shopping-basket',
            ],
            'name-raffle' => [
                'name' => 'Sorteo de nombres',
                'description' => 'Sorteo de nombres con multiples ganadores.',
                'icon' => 'fas fa-random',
            ],
        ];

        $utilityDefaults = $knownUtilities[$utility] ?? [
            'name' => Str::title(str_replace('-', ' ', $utility)),
            'description' => null,
            'icon' => null,
        ];

        $utilityModel = Utility::firstOrCreate(
            ['slug' => $utility],
            [
                'name' => $utilityDefaults['name'],
                'description' => $utilityDefaults['description'],
                'icon' => $utilityDefaults['icon'],
                'is_active' => true,
            ]
        );
        $utilitySlug = $utilityModel?->slug ?? $utility;
        $plan = $validated['plan'];
        $amount = (float) (config("mercadopago.plans.{$plan}") ?? 0);
        $currency = (string) config('mercadopago.currency', 'PEN');

        if ($amount <= 0) {
            return redirect()->back()->withErrors(['msg' => 'El plan seleccionado no tiene precio configurado.']);
        }

        $payment = UtilityPayment::create([
            'uuid' => $this->mp->buildExternalReference(),
            'utility_id' => $utilityModel?->id,
            'utility_slug' => $utilityModel?->slug ?? $utility,
            'user_id' => Auth::id(),
            'guest_id' => Auth::check() ? null : $this->guestService->getGuestId(),
            'plan' => $plan,
            'currency' => $currency,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        if (app()->environment('local')) {
            $payment->update([
                'mp_status' => 'approved',
                'mp_status_detail' => 'local_bypass',
                'mp_payment_type' => 'local',
                'status' => 'approved',
                'mp_response' => [
                    'status' => 'approved',
                    'status_detail' => 'local_bypass',
                    'payment_type_id' => 'local',
                ],
            ]);
            $this->subscriptionActivator->activateFromPayment($payment);

            return redirect()->route('payments.return', [
                'status' => 'approved',
                'utility' => $utilitySlug,
            ]);
        }

        $successPath = (string) config('mercadopago.success_url', '/payments/return');
        $failurePath = (string) config('mercadopago.failure_url', '/payments/return');
        $pendingPath = (string) config('mercadopago.pending_url', '/payments/return');

        $successPath = $successPath !== '' ? $successPath : '/payments/return';
        $failurePath = $failurePath !== '' ? $failurePath : '/payments/return';
        $pendingPath = $pendingPath !== '' ? $pendingPath : '/payments/return';

        $baseUrl = rtrim((string) $request->getSchemeAndHttpHost(), '/');
        if ($baseUrl === '') {
            $baseUrl = rtrim((string) config('app.url', ''), '/');
        }
        if ($baseUrl === '') {
            $baseUrl = 'http://127.0.0.1:8000';
        }

        $normalizeUrl = static function (string $path) use ($baseUrl): string {
            if ($path === '') {
                return $baseUrl . '/payments/return';
            }
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return $baseUrl . '/' . ltrim($path, '/');
        };
        $appendUtilityQuery = static function (string $path) use ($utilitySlug): string {
            if ($utilitySlug === '') {
                return $path;
            }
            return str_contains($path, '?')
                ? $path . '&utility=' . urlencode($utilitySlug)
                : $path . '?utility=' . urlencode($utilitySlug);
        };

        $payload = [
            'items' => [
                [
                    'title' => "Plan {$plan} - " . ($utilityModel?->name ?? $utility),
                    'quantity' => 1,
                    'currency_id' => $currency,
                    'unit_price' => $amount,
                ],
            ],
            'external_reference' => $payment->uuid,
            'back_urls' => [
                'success' => $appendUtilityQuery($normalizeUrl($successPath)),
                'failure' => $appendUtilityQuery($normalizeUrl($failurePath)),
                'pending' => $appendUtilityQuery($normalizeUrl($pendingPath)),
            ],
            'auto_return' => 'approved',
            'notification_url' => url('/api/payments/webhook'),
        ];

        try {
            $pref = $this->mp->createPreference($payload);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['msg' => 'No se pudo crear el pago: ' . $e->getMessage()]);
        }

        $payment->update([
            'mp_preference_id' => $pref['id'] ?? null,
            'mp_response' => $pref,
        ]);

        $initPoint = $pref['init_point'] ?? null;
        if (!$initPoint) {
            return redirect()->back()->withErrors(['msg' => 'Mercado Pago no devolvio el enlace de pago.']);
        }

        return redirect()->away($initPoint);
    }

    public function paymentReturn(Request $request)
    {
        $utilitySlug = (string) $request->query('utility', '');
        $returnUrl = $this->resolveUtilityReturnUrl($utilitySlug);
        if ($returnUrl === '') {
            $returnUrl = url()->previous();
        }

        return view('payments.return', [
            'status' => $request->query('status', 'pending'),
            'returnUrl' => $returnUrl,
        ]);
    }

    public function webhook(Request $request)
    {
        $topic = (string) ($request->input('topic') ?? $request->input('type') ?? '');
        $action = (string) ($request->input('action') ?? '');
        $paymentId = $request->input('data.id')
            ?? $request->input('id')
            ?? $request->query('id');

        Log::info('mp_webhook_received', [
            'topic' => $topic,
            'action' => $action,
            'payment_id' => $paymentId,
            'has_data' => (bool) $request->input('data'),
        ]);

        if (($topic !== 'payment' && !str_starts_with($action, 'payment')) || !$paymentId) {
            return response()->json(['ok' => true]);
        }

        try {
            $data = $this->mp->getPayment((string) $paymentId);
        } catch (\Throwable $e) {
            Log::error('mp_webhook_get_payment_failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false], 500);
        }

        $external = $data['external_reference'] ?? null;
        Log::info('mp_payment_fetched', [
            'payment_id' => $paymentId,
            'external_reference' => $external,
            'status' => $data['status'] ?? null,
            'status_detail' => $data['status_detail'] ?? null,
            'payment_type' => $data['payment_type_id'] ?? null,
        ]);
        if (!$external) {
            return response()->json(['ok' => true]);
        }

        $payment = UtilityPayment::where('uuid', $external)->first();
        if (!$payment) {
            Log::warning('mp_payment_not_found', [
                'external_reference' => $external,
                'payment_id' => $paymentId,
            ]);
            return response()->json(['ok' => true]);
        }

        $payment->update([
            'mp_payment_id' => (string) ($data['id'] ?? ''),
            'mp_status' => $data['status'] ?? null,
            'mp_status_detail' => $data['status_detail'] ?? null,
            'mp_payment_type' => $data['payment_type_id'] ?? null,
            'status' => $data['status'] ?? $payment->status,
            'mp_response' => $data,
        ]);

        if (($payment->status ?? null) === 'approved') {
            Log::info('mp_payment_approved', [
                'utility_payment_id' => $payment->id,
                'external_reference' => $payment->uuid,
            ]);
            $this->subscriptionActivator->activateFromPayment($payment);
        }

        return response()->json(['ok' => true]);
    }

    private function resolveUtilityReturnUrl(string $slug): string
    {
        return match ($slug) {
            'offer-alert' => route('offer-alerts.index'),
            'currency-alert' => route('currency-alert.index'),
            'supermarket-comparator' => route('supermarket-comparator.index'),
            'name-raffle' => route('name-raffle.index'),
            default => '',
        };
    }
}


