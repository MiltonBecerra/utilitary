<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Models\Subscription;
use Carbon\Carbon;

class AssignFreePlanToNewUser
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Registered  $event
     * @return void
     */
    public function handle(Registered $event)
    {
        $user = $event->user;

        // Check if user already has a subscription
        $existingSubscription = Subscription::where('user_id', $user->id)->first();

        if (!$existingSubscription) {
            // Create a free plan subscription valid for 1 year
            Subscription::create([
                'user_id' => $user->id,
                'plan_type' => 'free',
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addYear(),
            ]);
        }
    }
}
