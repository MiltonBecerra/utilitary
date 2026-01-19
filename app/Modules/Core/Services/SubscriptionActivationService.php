<?php

namespace App\Modules\Core\Services;

use App\Models\Subscription;
use App\Models\Utility;
use App\Models\UtilityPayment;
use Illuminate\Support\Carbon;
use App\Models\Alert;
use App\Models\OfferAlert;

class SubscriptionActivationService
{
    public function activateFromPayment(UtilityPayment $payment): ?Subscription
    {
        if (($payment->status ?? '') !== 'approved') {
            return null;
        }

        if ($payment->subscription_id) {
            return Subscription::find($payment->subscription_id);
        }

        $plan = (string) ($payment->plan ?? '');
        if (!in_array($plan, ['basic', 'pro'], true)) {
            return null;
        }

        $durationMonths = (int) config('mercadopago.duration_months', 1);
        if ($durationMonths < 1) {
            $durationMonths = 1;
        }

        $utility = null;
        if ($payment->utility_id) {
            $utility = Utility::find($payment->utility_id);
        }
        if (!$utility && $payment->utility_slug) {
            $utility = Utility::where('slug', $payment->utility_slug)->first();
        }

        $current = $this->findCurrentSubscription($payment, $utility?->id);
        $baseDate = $current && $current->ends_at
            ? Carbon::parse($current->ends_at)->greaterThan(now()) ? Carbon::parse($current->ends_at) : now()
            : now();

        if ($current && $current->plan_type === $plan) {
            $current->update([
                'ends_at' => $baseDate->copy()->addMonths($durationMonths)->toDateString(),
            ]);

            $this->attachUtility($current, $utility);

            $payment->update(['subscription_id' => $current->id]);
            $this->restoreAlertsForPlan($current, $utility);
            return $current;
        }

        $subscription = Subscription::create([
            'user_id' => $payment->user_id,
            'guest_id' => $payment->guest_id,
            'plan_type' => $plan,
            'starts_at' => now()->toDateString(),
            'ends_at' => $baseDate->copy()->addMonths($durationMonths)->toDateString(),
        ]);

        $this->attachUtility($subscription, $utility);

        $payment->update(['subscription_id' => $subscription->id]);
        $this->restoreAlertsForPlan($subscription, $utility);
        return $subscription;
    }

    private function findCurrentSubscription(UtilityPayment $payment, ?int $utilityId): ?Subscription
    {
        $query = Subscription::query()->active();

        if ($payment->user_id) {
            $query->where('user_id', $payment->user_id);
        } elseif ($payment->guest_id) {
            $query->where('guest_id', $payment->guest_id);
        } else {
            return null;
        }

        if ($utilityId) {
            $query->whereHas('utilities', function ($u) use ($utilityId) {
                $u->where('utilities.id', $utilityId);
            });
        }

        return $query->orderByDesc('ends_at')->first();
    }

    private function attachUtility(Subscription $subscription, ?Utility $utility): void
    {
        if (!$utility) {
            return;
        }

        $subscription->utilities()->syncWithoutDetaching([$utility->id]);
    }

    private function restoreAlertsForPlan(Subscription $subscription, ?Utility $utility): void
    {
        if (!$utility) {
            return;
        }

        $slug = (string) $utility->slug;
        if ($slug === 'currency-alert') {
            $this->restoreCurrencyAlerts($subscription, $utility);
            return;
        }

        if ($slug === 'offer-alert') {
            $this->restoreOfferAlerts($subscription, $utility);
        }
    }

    private function restoreCurrencyAlerts(Subscription $subscription, Utility $utility): void
    {
        $plan = (string) ($subscription->plan_type ?? 'free');
        $limit = match ($plan) {
            'free' => 1,
            'basic' => 10,
            'pro' => 30,
            default => 1,
        };

        if (in_array($plan, ['basic', 'pro'], true)) {
            $this->restoreRecurringAlerts($subscription);
        }

        $query = Alert::whereIn('status', ['active', 'fallback_email', 'triggered']);

        if ($subscription->user_id) {
            $query->where('user_id', $subscription->user_id);
        } elseif ($subscription->guest_id) {
            $query->where('guest_id', $subscription->guest_id);
        } else {
            return;
        }

        $activeCount = $query->count();
        $available = $limit === -1 ? null : max(0, $limit - $activeCount);

        if ($available === null || $available > 0) {
            $toRestore = Alert::where('plan_deactivated', true)
                ->whereIn('plan_deactivated_from_status', ['active', 'fallback_email', 'triggered'])
                ->orderBy('created_at', 'desc');

            if ($subscription->user_id) {
                $toRestore->where('user_id', $subscription->user_id);
            } else {
                $toRestore->where('guest_id', $subscription->guest_id);
            }

            if ($available !== null) {
                $toRestore->limit($available);
            }

            $alerts = $toRestore->get();
            foreach ($alerts as $alert) {
                $status = $plan === 'pro'
                    ? 'active'
                    : ($alert->channel === 'whatsapp' ? 'fallback_email' : 'active');

                $alert->update([
                    'status' => $status,
                    'plan_deactivated' => false,
                    'plan_deactivated_from_status' => null,
                ]);
            }
        }

        if ($plan === 'pro') {
            Alert::where('channel', 'whatsapp')
                ->where('status', 'fallback_email')
                ->when($subscription->user_id, fn ($q) => $q->where('user_id', $subscription->user_id))
                ->when($subscription->guest_id, fn ($q) => $q->where('guest_id', $subscription->guest_id))
                ->update(['status' => 'active']);
        }
    }

    private function restoreOfferAlerts(Subscription $subscription, Utility $utility): void
    {
        $plan = (string) ($subscription->plan_type ?? 'free');
        $limit = match ($plan) {
            'free' => 2,
            'basic' => 10,
            'pro' => 30,
            default => 2,
        };

        $query = OfferAlert::where('utility_id', $utility->id)
            ->whereIn('status', ['active', 'fallback_email']);

        if ($subscription->user_id) {
            $query->where('user_id', $subscription->user_id);
        } elseif ($subscription->guest_id) {
            $query->where('guest_id', $subscription->guest_id);
        } else {
            return;
        }

        $activeCount = $query->count();
        $available = $limit === -1 ? null : max(0, $limit - $activeCount);

        if ($available === null || $available > 0) {
            $toRestore = OfferAlert::where('utility_id', $utility->id)
                ->where('plan_deactivated', true)
                ->whereIn('plan_deactivated_from_status', ['active', 'fallback_email'])
                ->orderBy('created_at', 'desc');

            if ($subscription->user_id) {
                $toRestore->where('user_id', $subscription->user_id);
            } else {
                $toRestore->where('guest_id', $subscription->guest_id);
            }

            if ($available !== null) {
                $toRestore->limit($available);
            }

            $alerts = $toRestore->get();
            foreach ($alerts as $alert) {
                $status = $plan === 'pro'
                    ? 'active'
                    : ($alert->channel === 'whatsapp' ? 'fallback_email' : 'active');

                $alert->update([
                    'status' => $status,
                    'plan_deactivated' => false,
                    'plan_deactivated_from_status' => null,
                ]);
            }
        }

        if ($plan === 'pro') {
            OfferAlert::where('utility_id', $utility->id)
                ->where('channel', 'whatsapp')
                ->where('status', 'fallback_email')
                ->when($subscription->user_id, fn ($q) => $q->where('user_id', $subscription->user_id))
                ->when($subscription->guest_id, fn ($q) => $q->where('guest_id', $subscription->guest_id))
                ->update([
                    'status' => 'active',
                ]);
        }
    }

    private function restoreRecurringAlerts(Subscription $subscription): void
    {
        $query = Alert::where('frequency', 'recurring_paused');

        if ($subscription->user_id) {
            $query->where('user_id', $subscription->user_id);
        } elseif ($subscription->guest_id) {
            $query->where('guest_id', $subscription->guest_id);
        } else {
            return;
        }

        $query->update(['frequency' => 'recurring']);
    }
}
