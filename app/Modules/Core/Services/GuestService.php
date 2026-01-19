<?php

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\Cookie;
use App\Models\GuestConsent;
use Illuminate\Support\Str;

class GuestService
{
    const COOKIE_NAME = 'currency_alert_guest_id';
    const COOKIE_LIFETIME = 60 * 24 * 365; // 1 year

    public function getGuestId()
    {
        $guestId = Cookie::get(self::COOKIE_NAME);

        if (!$guestId) {
            $guestId = (string) Str::uuid();
            Cookie::queue(self::COOKIE_NAME, $guestId, self::COOKIE_LIFETIME);
        }

        return $guestId;
    }

    public function hasGuestId()
    {
        return Cookie::has(self::COOKIE_NAME);
    }

    public function hasAcceptedTerms(): bool
    {
        $guestId = $this->getGuestId();
        return GuestConsent::where('guest_id', $guestId)->exists();
    }

    /**
     * Get the active subscription for the current guest.
     * 
     * @return \App\Models\Subscription|null
     */
    public function getGuestSubscription($utilityId = null)
    {
        $guestId = $this->getGuestId();
        
        $query = \App\Models\Subscription::forGuest($guestId)
            ->active()
            ->latest('ends_at');

        if ($utilityId) {
            $query = $query->whereHas('utilities', function ($u) use ($utilityId) {
                $u->where('utilities.id', $utilityId);
            });
        }

        return $query->first();
    }

    /**
     * Get the guest's current plan type.
     * 
     * @return string 'free', 'basic', or 'pro'
     */
    public function getGuestPlan($utilityId = null)
    {
        $subscription = $this->getGuestSubscription($utilityId);
        return $subscription ? $subscription->plan_type : 'free';
    }

    /**
     * Check if guest can create a new alert based on their plan.
     * 
     * @return bool
     */
    public function canGuestCreateAlert($utilityId = null)
    {
        $guestId = $this->getGuestId();
        $plan = $this->getGuestPlan($utilityId);
        
        $currentAlertsCount = \App\Models\Alert::where('guest_id', $guestId)
            ->whereIn('status', ['active', 'fallback_email', 'triggered'])
            ->count();

        $limit = $this->getGuestAlertLimit();
        
        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $currentAlertsCount < $limit;
    }

    /**
     * Check if guest reached monthly alert creation limit.
     */
    public function hasGuestReachedMonthlyAlertLimit($utilityId = null): bool
    {
        $limit = $this->getGuestMonthlyAlertLimit($utilityId);
        if ($limit === -1) {
            return false;
        }

        $guestId = $this->getGuestId();
        $query = \App\Models\Alert::where('guest_id', $guestId)
            ->where('created_at', '>=', now()->startOfMonth());

        return $query->count() >= $limit;
    }

    /**
     * Get monthly alert creation limit by plan.
     *
     * @return int Returns -1 for unlimited
     */
    public function getGuestMonthlyAlertLimit($utilityId = null)
    {
        $plan = $this->getGuestPlan($utilityId);

        return match ($plan) {
            'basic' => 5,
            'pro' => 15,
            default => -1,
        };
    }

    /**
     * Get the alert limit for the guest's current plan.
     * 
     * @return int Returns -1 for unlimited
     */
    public function getGuestAlertLimit($utilityId = null)
    {
        $plan = $this->getGuestPlan($utilityId);

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
     * Check if guest can use WhatsApp notifications.
     * 
     * @return bool
     */
    public function canGuestUseWhatsApp($utilityId = null)
    {
        return $this->getGuestPlan($utilityId) === 'pro';
    }

    /**
     * Check if guest can use recurring alerts.
     * 
     * @return bool
     */
    public function canGuestUseRecurringAlerts($utilityId = null)
    {
        $plan = $this->getGuestPlan($utilityId);
        return in_array($plan, ['basic', 'pro']);
    }
}
