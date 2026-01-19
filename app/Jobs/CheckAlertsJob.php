<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\ExchangeRate;
use App\Models\Subscription;
use App\Models\Utility;
use App\Notifications\AlertTriggered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;

class CheckAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $utilityId = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Starting CheckAlertsJob...");

        $this->utilityId = Utility::where('slug', 'currency-alert')->value('id');
        $this->applyFallbackForDowngradedPlans();

        $activeAlerts = Alert::whereIn('status', ['active', 'fallback_email'])->get();
        $now = now();

        foreach ($activeAlerts as $alert) {
            $latestRate = ExchangeRate::where('exchange_source_id', $alert->exchange_source_id)
                ->latest()
                ->first();

            if (!$latestRate) {
                continue;
            }

            // Determine which price to compare (Buy or Sell?)
            // Usually users want to buy dollars (Sell price of the casa de cambio) or sell dollars (Buy price of the casa de cambio).
            // Let's assume the user sets a target for the "Sell" price (what they pay to buy USD) or "Buy" price (what they get for selling USD).
            // For simplicity, let's assume the alert is on the "Sell Price" (buying USD) as that's most common, 
            // OR we should have a field in Alert for 'type' (buy/sell).
            // The current schema doesn't have 'type' (buy/sell), just 'target_price' and 'condition'.
            // Let's assume it compares against the SELL price (buying USD) for now, or check if we can infer.
            // Actually, usually you want to know if the dollar DROPS (to buy) or RISES (to sell).
            // If condition is 'below', they probably want to BUY (compare against Sell Price).
            // If condition is 'above', they probably want to SELL (compare against Buy Price).
            
            $currentPrice = ($alert->condition == 'below') ? $latestRate->sell_price : $latestRate->buy_price;

            $triggered = false;
            if ($alert->condition == 'above' && $currentPrice >= $alert->target_price) {
                $triggered = true;
            } elseif ($alert->condition == 'below' && $currentPrice <= $alert->target_price) {
                $triggered = true;
            }

            if ($triggered) {
                if (!$this->canNotify($alert, $now)) {
                    continue;
                }

                $this->sendNotification($alert, $latestRate);
                $this->touchNotificationCounters($alert, $now);
                
                if ($alert->frequency == 'once') {
                    $alert->update(['status' => 'triggered']);
                }
            }
        }

        Log::info("CheckAlertsJob completed.");
    }

    protected function canNotify(Alert $alert, Carbon $now): bool
    {
        if ($alert->frequency === 'recurring' && !$this->canUseRecurringForAlert($alert)) {
            return false;
        }

        if ($alert->frequency !== 'recurring') {
            return true;
        }

        $lastNotified = $alert->last_notified_at;
        if ($lastNotified && $lastNotified->diffInMinutes($now) < 60) {
            return false;
        }

        $dailyDate = $alert->daily_notified_date;
        $dailyCount = (int) ($alert->daily_notified_count ?? 0);
        if ($dailyDate && $dailyDate->isSameDay($now) && $dailyCount >= 5) {
            return false;
        }

        return true;
    }

    protected function touchNotificationCounters(Alert $alert, Carbon $now): void
    {
        $dailyDate = $alert->daily_notified_date;
        $dailyCount = (int) ($alert->daily_notified_count ?? 0);

        if (!$dailyDate || !$dailyDate->isSameDay($now)) {
            $dailyDate = $now->toDateString();
            $dailyCount = 0;
        }

        $alert->update([
            'last_notified_at' => $now,
            'daily_notified_date' => $dailyDate,
            'daily_notified_count' => $dailyCount + 1,
        ]);
    }

    protected function sendNotification($alert, $rate)
    {
        Log::info("Triggering alert {$alert->id} for {$alert->contact_detail} via {$alert->channel}");

        $shouldFallbackEmail = $alert->channel === 'whatsapp' && $this->shouldFallbackToEmail($alert);
        $channelToUse = $shouldFallbackEmail ? 'email' : $alert->channel;
        $emailTarget = $alert->user ? $alert->user->email : $alert->contact_detail;

        if ($channelToUse === 'email') {
            try {
                Notification::route('mail', $emailTarget)
                    ->notify(new AlertTriggered($alert, $rate));
                if ($shouldFallbackEmail && $alert->status !== 'fallback_email') {
                    $alert->update(['status' => 'fallback_email']);
                }
            } catch (\Exception $e) {
                Log::error("Failed to send email alert: " . $e->getMessage());
            }
        } elseif ($alert->channel == 'whatsapp') {
            // Placeholder for WhatsApp
            Log::info("WhatsApp alert sent to {$alert->contact_detail}: Target {$alert->target_price} reached.");
        }
    }

    protected function shouldFallbackToEmail(Alert $alert): bool
    {
        if ($alert->user) {
            return !$alert->user->canUseWhatsApp($this->utilityId);
        }

        if ($alert->guest_id) {
            $subscription = Subscription::forGuest($alert->guest_id)
                ->active()
                ->when($this->utilityId, function ($query) {
                    $query->whereHas('utilities', function ($u) {
                        $u->where('utilities.id', $this->utilityId);
                    });
                })
                ->latest('ends_at')
                ->first();
            $plan = $subscription ? $subscription->plan_type : 'free';
            return $plan !== 'pro';
        }

        return false;
    }

    /**
     * For users/guests sin plan Pro, marcar alertas WhatsApp como fallback_email.
     */
    protected function applyFallbackForDowngradedPlans(): void
    {
        // Usuarios registrados
        $userIdsWithoutPro = Subscription::whereDate('ends_at', '>=', now()->toDateString())
            ->where('plan_type', '!=', 'pro')
            ->when($this->utilityId, function ($query) {
                $query->whereHas('utilities', function ($u) {
                    $u->where('utilities.id', $this->utilityId);
                });
            })
            ->pluck('user_id')
            ->unique()
            ->filter();

        if ($userIdsWithoutPro->isNotEmpty()) {
            Alert::whereIn('user_id', $userIdsWithoutPro)
                ->where('channel', 'whatsapp')
                ->where('status', 'active')
                ->update(['status' => 'fallback_email']);
        }

        // Invitados
        $guestIdsWithoutPro = Subscription::whereNotNull('guest_id')
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->where('plan_type', '!=', 'pro')
            ->when($this->utilityId, function ($query) {
                $query->whereHas('utilities', function ($u) {
                    $u->where('utilities.id', $this->utilityId);
                });
            })
            ->pluck('guest_id')
            ->unique();

        if ($guestIdsWithoutPro->isNotEmpty()) {
            Alert::whereIn('guest_id', $guestIdsWithoutPro)
                ->where('channel', 'whatsapp')
                ->where('status', 'active')
                ->update(['status' => 'fallback_email']);
        }
    }

    protected function canUseRecurringForAlert(Alert $alert): bool
    {
        if ($alert->user) {
            return $alert->user->canUseRecurringAlerts($this->utilityId);
        }

        if ($alert->guest_id) {
            $subscription = Subscription::forGuest($alert->guest_id)
                ->active()
                ->when($this->utilityId, function ($query) {
                    $query->whereHas('utilities', function ($u) {
                        $u->where('utilities.id', $this->utilityId);
                    });
                })
                ->latest('ends_at')
                ->first();
            $plan = $subscription ? $subscription->plan_type : 'free';
            return in_array($plan, ['basic', 'pro'], true);
        }

        return false;
    }
}
