<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Models\Alert;
use App\Models\Subscription;
use App\Models\GuestEmail;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class MigrateGuestDataOnRegistration
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
        $guestId = Cookie::get('currency_alert_guest_id');

        if (!$guestId) {
            return; // No guest data to migrate
        }

        DB::transaction(function () use ($user, $guestId) {
            // Migrate alerts from guest to user
            Alert::where('guest_id', $guestId)
                ->update([
                    'user_id' => $user->id,
                    'guest_id' => null
                ]);

            // Migrate subscriptions from guest to user
            Subscription::where('guest_id', $guestId)
                ->update([
                    'user_id' => $user->id,
                    'guest_id' => null
                ]);

            // Optionally delete guest email record (data is now associated with user)
            GuestEmail::where('guest_id', $guestId)->delete();
        });

        \Log::info("Migrated guest data for guest_id: {$guestId} to user_id: {$user->id}");
    }
}
