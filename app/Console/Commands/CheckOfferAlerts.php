<?php

namespace App\Console\Commands;

use App\Models\OfferAlert;
use App\Models\OfferPriceHistory;
use App\Models\Subscription;
use App\Models\Utility;
use App\Notifications\OfferAlertTriggered;
use App\Modules\Utilities\OfferAlerts\Services\OfferPriceScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckOfferAlerts extends Command
{
    protected $signature = 'app:check-offer-alerts';
    protected $description = 'Check offer alerts and send notifications';

    public function __construct(
        protected OfferPriceScraperService $scraper,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $utility = Utility::where('slug', 'offer-alert')->first();
        $utilityId = $utility?->id;

        $alerts = OfferAlert::whereIn('status', ['active', 'fallback_email'])->get();
        Log::info('Starting CheckOfferAlerts', ['count' => $alerts->count()]);

        foreach ($alerts as $alert) {
            try {
                $previousPrice = $alert->current_price !== null ? (float) $alert->current_price : null;
                $plan = $this->resolvePlanForAlert($alert, $utilityId);

                // Ripley solo en Pro
                if (($alert->store === 'ripley' || $this->scraper->detectStore($alert->url) === 'ripley') && $plan !== 'pro') {
                    Log::info('offer_alert_skipped_ripley_by_plan', ['offer_alert_id' => $alert->id, 'plan' => $plan]);
                    continue;
                }

                $product = $this->scraper->fetchProduct($alert->url);
                $store = $product['store'] ?? $this->scraper->detectStore($alert->url);

                $publicPrice = $product['public_price'] ?? $product['price'] ?? null;
                $cmrPrice = $product['cmr_price'] ?? null;
                $effectivePriceType = $alert->price_type;
                $selected = $effectivePriceType === 'cmr' ? $cmrPrice : $publicPrice;

                // Si el precio no público no existe, usar el precio público (fallback global).
                if ($effectivePriceType === 'cmr' && $selected === null && $publicPrice !== null) {
                    Log::info('offer_alert_price_type_fallback_to_public', [
                        'offer_alert_id' => $alert->id,
                        'store' => $store,
                        'url' => $alert->url,
                    ]);
                    $selected = $publicPrice;
                    $effectivePriceType = 'public';
                }

                if ($selected === null) {
                    Log::warning('offer_alert_price_null', ['offer_alert_id' => $alert->id, 'url' => $alert->url]);
                    continue;
                }

                $selected = (float) $selected;

                $alert->update([
                    'title' => $product['title'] ?? $alert->title,
                    'store' => $store,
                    'image_url' => $product['image_url'] ?? $alert->image_url,
                    'current_price' => $selected,
                    'public_price' => $publicPrice,
                    'cmr_price' => $cmrPrice,
                    'price_type' => $effectivePriceType,
                    'last_checked_at' => now(),
                ]);

                OfferPriceHistory::create([
                    'offer_alert_id' => $alert->id,
                    'price' => $selected,
                    'checked_at' => now(),
                ]);

                $shouldNotify = $this->shouldNotify($alert, $selected, $previousPrice, $plan);
                if (!$shouldNotify) {
                    continue;
                }

                $channel = $plan === 'pro' ? ($alert->channel ?: 'email') : 'email';

                if ($channel === 'whatsapp') {
                    // Placeholder: integración WhatsApp pendiente
                    Log::info('offer_alert_whatsapp_placeholder', ['offer_alert_id' => $alert->id, 'to' => $alert->contact_phone]);
                } else {
                    Notification::route('mail', $alert->contact_email)
                        ->notify(new OfferAlertTriggered($alert, $selected));
                }

                // Guardar último precio notificado
                $alert->update(['last_notified_price' => $selected]);

                // Free: solo 1 correo por alerta
                if ($plan === 'free') {
                    $alert->update(['status' => 'triggered']);
                }
            } catch (\Throwable $e) {
                Log::error('offer_alert_check_failed', ['offer_alert_id' => $alert->id, 'error' => $e->getMessage()]);
            }
        }

        Log::info('CheckOfferAlerts completed');
        return 0;
    }

    protected function resolvePlanForAlert(OfferAlert $alert, ?int $utilityId): string
    {
        if ($alert->user_id) {
            $user = $alert->user;
            return $user ? $user->getActivePlan($utilityId) : 'free';
        }

        if ($alert->guest_id) {
            $query = Subscription::forGuest($alert->guest_id)
                ->active()
                ->latest('ends_at');

            if ($utilityId) {
                $query = $query->where(function ($q) use ($utilityId) {
                    $q->whereHas('utilities', function ($u) use ($utilityId) {
                        $u->where('utilities.id', $utilityId);
                    })->orWhereDoesntHave('utilities');
                });
            }

            $subscription = $query->first();
            return $subscription ? $subscription->plan_type : 'free';
        }

        return 'free';
    }

    protected function shouldNotify(OfferAlert $alert, float $currentPrice, ?float $previousPrice, string $plan): bool
    {
        // Free: 1 correo por alerta (si ya notificó una vez, no notificar más)
        if ($plan === 'free' && $alert->last_notified_price !== null) {
            return false;
        }

        $lastNotified = $alert->last_notified_price !== null ? (float) $alert->last_notified_price : null;

        if ($alert->notify_on_any_drop) {
            // Primera lectura: baseline sin notificación
            if ($previousPrice === null) {
                return false;
            }
            if ($currentPrice >= $previousPrice) {
                return false;
            }

            return $lastNotified === null || $currentPrice < $lastNotified;
        }

        if ($alert->target_price !== null) {
            $target = (float) $alert->target_price;
            if ($currentPrice > $target) {
                return false;
            }

            // Evitar spam: notificar solo si baja más que el último notificado
            return $lastNotified === null || $currentPrice < $lastNotified;
        }

        return false;
    }
}

