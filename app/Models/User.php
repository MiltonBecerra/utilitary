<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'password',
        'terms_accepted_at',
        'terms_accepted_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'terms_accepted_at' => 'datetime',
    ];

    /**
     * Get all subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    /**
     * Get the active subscription for the user.
     */
    public function activeSubscription()
    {
        return $this->hasOne(\App\Models\Subscription::class)
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->orderByDesc('ends_at');
    }

    /**
     * Get the user's active plan type.
     * 
     * @return string 'free', 'basic', or 'pro'
     */
    public function getActivePlan($utilityId = null)
    {
        $query = \App\Models\Subscription::where('user_id', $this->id)
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->orderByDesc('ends_at');

        if ($utilityId) {
            $query = $query->whereHas('utilities', function ($u) use ($utilityId) {
                $u->where('utilities.id', $utilityId);
            });
        }

        $subscription = $query->first();
        return $subscription ? $subscription->plan_type : 'free';
    }

    /**
     * Check if user has an active subscription.
     * 
     * @return bool
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Check if user can create a new alert based on their plan.
     * 
     * @return bool
     */
    public function canCreateAlert($utilityId = null)
    {
        $plan = $this->getActivePlan($utilityId);
        $currentAlertsCount = \App\Models\Alert::where('user_id', $this->id)
            ->whereIn('status', ['active', 'fallback_email', 'triggered'])
            ->count();

        $limit = $this->getAlertLimit();
        
        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $currentAlertsCount < $limit;
    }

    /**
     * Check if user reached monthly alert creation limit.
     */
    public function hasReachedMonthlyAlertLimit($utilityId = null): bool
    {
        $limit = $this->getMonthlyAlertLimit($utilityId);
        if ($limit === -1) {
            return false;
        }

        $query = \App\Models\Alert::where('user_id', $this->id)
            ->where('created_at', '>=', now()->startOfMonth());

        return $query->count() >= $limit;
    }

    /**
     * Get monthly alert creation limit by plan.
     *
     * @return int Returns -1 for unlimited
     */
    public function getMonthlyAlertLimit($utilityId = null)
    {
        $plan = $this->getActivePlan($utilityId);

        return match ($plan) {
            'basic' => 5,
            'pro' => 15,
            default => -1,
        };
    }

    /**
     * Get the alert limit for the user's current plan.
     * 
     * @return int Returns -1 for unlimited
     */
    public function getAlertLimit($utilityId = null)
    {
        $plan = $this->getActivePlan($utilityId);

        switch ($plan) {
            case 'free':
                return 1;
            case 'basic':
                return 5;
            case 'pro':
                return 15;
            default:
                return 1;
        }
    }

    /**
     * Check if user can use WhatsApp notifications.
     * 
     * @return bool
     */
    public function canUseWhatsApp($utilityId = null)
    {
        return $this->getActivePlan($utilityId) === 'pro';
    }

    /**
     * Check if user can use recurring alerts.
     * 
     * @return bool
     */
    public function canUseRecurringAlerts($utilityId = null)
    {
        $plan = $this->getActivePlan($utilityId);
        return in_array($plan, ['basic', 'pro']);
    }
}
