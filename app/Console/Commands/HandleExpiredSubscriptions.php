<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Alert;
use App\Models\OfferAlert;
use App\Models\User;
use App\Models\GuestEmail;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class HandleExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:handle-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle expired subscriptions and deactivate excess alerts for free plan users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting subscription expiration handler...');

        $currencyUtility = Utility::where('slug', 'currency-alert')->first();
        $currencyUtilityId = $currencyUtility?->id;
        $offerUtility = Utility::where('slug', 'offer-alert')->first();
        $offerUtilityId = $offerUtility?->id;

        // Get all subscriptions that expired today
        $expiredSubscriptions = Subscription::where('ends_at', '<', Carbon::today())
            ->where('ends_at', '>=', Carbon::yesterday())
            ->get();

        $processedCount = 0;

        foreach ($expiredSubscriptions as $subscription) {
            DB::transaction(function () use ($subscription, $currencyUtilityId, $offerUtilityId, &$processedCount) {
                $this->downgradeSubscriptionToFree($subscription);

                // Handle user subscriptions
                if ($subscription->user_id) {
                    $user = User::find($subscription->user_id);
                    if ($user) {
                        $this->handleUserDowngrade($user, $subscription, $currencyUtilityId, $offerUtilityId);
                        $this->sendExpirationEmail($user->email, $subscription->plan_type);
                    }
                }
                
                // Handle guest subscriptions
                if ($subscription->guest_id) {
                    $this->handleGuestDowngrade($subscription->guest_id, $subscription, $currencyUtilityId, $offerUtilityId);
                    
                    // Send email to guest if we have their email
                    $guestEmail = GuestEmail::where('guest_id', $subscription->guest_id)->first();
                    if ($guestEmail) {
                        $this->sendExpirationEmail($guestEmail->email, $subscription->plan_type);
                    }
                }

                $processedCount++;
            });
        }

        $this->info("Processed {$processedCount} expired subscriptions.");

        // Also send reminder emails for subscriptions expiring in 3 days
        $this->sendExpirationReminders();

        return 0;
    }

    protected function downgradeSubscriptionToFree(Subscription $subscription): void
    {
        $subscription->update([
            'plan_type' => 'free',
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addYear(),
        ]);
    }

    /**
     * Handle user downgrade to free plan.
     *
     * @param User $user
     * @param Subscription $expiredSubscription
     * @return void
     */
    protected function handleUserDowngrade(User $user, Subscription $expiredSubscription, ?int $utilityId, ?int $offerUtilityId)
    {
        $this->info("Processing user {$user->id} - Plan {$expiredSubscription->plan_type} expired");

        $this->pauseRecurringAlerts($user->id, null);

        $plan = $this->resolveCurrentPlan($user->id, null, $utilityId);
        $this->enforceCurrencyAlertLimits($plan, $user->id, null, $utilityId);
        $offerPlan = $this->resolveCurrentPlan($user->id, null, $offerUtilityId);
        $this->enforceOfferAlertLimits($offerPlan, $user->id, null, $offerUtilityId);
    }

    /**
     * Handle guest downgrade to free plan.
     *
     * @param string $guestId
     * @param Subscription $expiredSubscription
     * @return void
     */
    protected function handleGuestDowngrade($guestId, Subscription $expiredSubscription, ?int $utilityId, ?int $offerUtilityId)
    {
        $this->info("Processing guest {$guestId} - Plan {$expiredSubscription->plan_type} expired");

        $this->pauseRecurringAlerts(null, $guestId);

        $plan = $this->resolveCurrentPlan(null, $guestId, $utilityId);
        $this->enforceCurrencyAlertLimits($plan, null, $guestId, $utilityId);
        $offerPlan = $this->resolveCurrentPlan(null, $guestId, $offerUtilityId);
        $this->enforceOfferAlertLimits($offerPlan, null, $guestId, $offerUtilityId);
    }

    protected function resolveCurrentPlan(?int $userId, ?string $guestId, ?int $utilityId): string
    {
        $query = Subscription::query()
            ->where('ends_at', '>=', Carbon::today())
            ->orderByDesc('ends_at');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($guestId) {
            $query->where('guest_id', $guestId);
        } else {
            return 'free';
        }

        if ($utilityId) {
            $query->where(function ($q) use ($utilityId) {
                $q->whereHas('utilities', function ($u) use ($utilityId) {
                    $u->where('utilities.id', $utilityId);
                })->orWhereDoesntHave('utilities');
            });
        }

        $subscription = $query->first();
        return $subscription ? $subscription->plan_type : 'free';
    }

    protected function enforceCurrencyAlertLimits(string $plan, ?int $userId, ?string $guestId, ?int $utilityId): void
    {
        $limit = match ($plan) {
            'free' => 1,
            'basic' => 10,
            'pro' => 30,
            default => 1,
        };

        if ($limit === -1) {
            return;
        }

        $query = Alert::whereIn('status', ['active', 'fallback_email', 'triggered'])
            ;

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('guest_id', $guestId);
        }

        $activeAlerts = $query->orderBy('created_at', 'desc')->get();

        if ($activeAlerts->count() > $limit) {
            $alertsToDeactivate = $activeAlerts->slice($limit);
            foreach ($alertsToDeactivate as $alert) {
                $alert->update([
                    'status' => 'inactive',
                    'plan_deactivated' => true,
                    'plan_deactivated_from_status' => $alert->status,
                ]);
                $this->info("  - Deactivated alert #{$alert->id}");
            }
        }

        if ($plan !== 'pro') {
            $alertsToFallback = $activeAlerts->take($limit)
                ->where('channel', 'whatsapp')
                ->where('status', 'active');

            foreach ($alertsToFallback as $alert) {
                $alert->update(['status' => 'fallback_email']);
            }
        }
    }

    protected function enforceOfferAlertLimits(string $plan, ?int $userId, ?string $guestId, ?int $utilityId): void
    {
        if (!$utilityId) {
            return;
        }

        $limit = match ($plan) {
            'free' => 2,
            'basic' => 10,
            'pro' => 30,
            default => 2,
        };

        $query = OfferAlert::whereIn('status', ['active', 'fallback_email'])
            ->where('utility_id', $utilityId);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('guest_id', $guestId);
        }

        $activeAlerts = $query->orderBy('created_at', 'desc')->get();

        if ($activeAlerts->count() > $limit) {
            $alertsToDeactivate = $activeAlerts->slice($limit);
            foreach ($alertsToDeactivate as $alert) {
                $alert->update([
                    'status' => 'inactive',
                    'plan_deactivated' => true,
                    'plan_deactivated_from_status' => $alert->status,
                ]);
                $this->info("  - Deactivated offer alert #{$alert->id}");
            }
        }

        if ($plan !== 'pro') {
            $alertsToFallback = $activeAlerts->take($limit)
                ->where('channel', 'whatsapp')
                ->whereIn('status', ['active', 'fallback_email']);

            foreach ($alertsToFallback as $alert) {
                $alert->update([
                    'status' => 'fallback_email',
                ]);
            }
        }
    }

    protected function pauseRecurringAlerts(?int $userId, ?string $guestId): void
    {
        $query = Alert::where('frequency', 'recurring');

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('guest_id', $guestId);
        }

        $query->update(['frequency' => 'recurring_paused']);
    }

    /**
     * Deactivate excess alerts, keeping only the most recent one active.
     *
     * @param int|null $userId
     * @param string|null $guestId
     * @return void
     */
    protected function deactivateExcessAlerts($userId, $guestId)
    {
        $query = Alert::where('status', 'active');

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('guest_id', $guestId);
        }

        $activeAlerts = $query->orderBy('created_at', 'desc')->get();

        if ($activeAlerts->count() > 1) {
            // Keep the most recent alert active, deactivate the rest
            $alertsToDeactivate = $activeAlerts->slice(1);
            
            foreach ($alertsToDeactivate as $alert) {
                $alert->update(['status' => 'inactive']);
                $this->info("  - Deactivated alert #{$alert->id}");
            }

            $deactivatedCount = $alertsToDeactivate->count();
            $owner = $userId ? "User {$userId}" : "Guest {$guestId}";
            $this->info("  {$owner}: Deactivated {$deactivatedCount} alerts (keeping most recent)");
        }
    }

    /**
     * Send expiration email notification.
     *
     * @param string $email
     * @param string $planType
     * @return void
     */
    protected function sendExpirationEmail($email, $planType)
    {
        try {
            Mail::raw(
                "Tu suscripción al plan " . strtoupper($planType) . " ha expirado.\n\n" .
                "Ahora estás en el plan FREE con las siguientes limitaciones:\n" .
                "- 1 alerta activa\n" .
                "- Solo notificaciones por Email\n" .
                "- Alertas de una sola vez\n\n" .
                "Tus alertas adicionales han sido desactivadas pero no eliminadas.\n" .
                "Puedes reactivarlas actualizando tu plan.\n\n" .
                "Visita nuestro sitio para actualizar tu plan.",
                function ($message) use ($email, $planType) {
                    $message->to($email)
                        ->subject('Tu Plan ' . strtoupper($planType) . ' ha Expirado');
                }
            );
            $this->info("  - Sent expiration email to {$email}");
        } catch (\Exception $e) {
            $this->error("  - Failed to send email to {$email}: " . $e->getMessage());
        }
    }

    /**
     * Send reminder emails for subscriptions expiring in 3 days.
     *
     * @return void
     */
    protected function sendExpirationReminders()
    {
        $expiringIn3Days = Subscription::where('ends_at', '=', Carbon::today()->addDays(3))
            ->get();

        foreach ($expiringIn3Days as $subscription) {
            $email = null;

            if ($subscription->user_id) {
                $user = User::find($subscription->user_id);
                $email = $user ? $user->email : null;
            } elseif ($subscription->guest_id) {
                $guestEmail = GuestEmail::where('guest_id', $subscription->guest_id)->first();
                $email = $guestEmail ? $guestEmail->email : null;
            }

            if ($email) {
                $this->sendReminderEmail($email, $subscription->plan_type, $subscription->ends_at);
            }
        }
    }

    /**
     * Send reminder email.
     *
     * @param string $email
     * @param string $planType
     * @param Carbon $endsAt
     * @return void
     */
    protected function sendReminderEmail($email, $planType, $endsAt)
    {
        try {
            Mail::raw(
                "Tu plan " . strtoupper($planType) . " expirará en 3 días.\n\n" .
                "Fecha de expiración: " . $endsAt->format('d/m/Y') . "\n\n" .
                "Renueva tu plan para seguir disfrutando de todos los beneficios.\n\n" .
                "Visita nuestro sitio para renovar.",
                function ($message) use ($email, $planType) {
                    $message->to($email)
                        ->subject('Recordatorio: Tu Plan ' . strtoupper($planType) . ' Expira Pronto');
                }
            );
            $this->info("  - Sent reminder email to {$email}");
        } catch (\Exception $e) {
            $this->error("  - Failed to send reminder to {$email}: " . $e->getMessage());
        }
    }
}
