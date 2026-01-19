<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestEmail extends Model
{
    protected $fillable = [
        'guest_id',
        'email',
    ];

    /**
     * Get subscriptions for this guest.
     */
    public function subscriptions()
    {
        return Subscription::where('guest_id', $this->guest_id)->get();
    }

    /**
     * Get alerts for this guest.
     */
    public function alerts()
    {
        return Alert::where('guest_id', $this->guest_id)->get();
    }
}
