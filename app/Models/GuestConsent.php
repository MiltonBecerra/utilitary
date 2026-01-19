<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestConsent extends Model
{
    protected $fillable = [
        'guest_id',
        'accepted_at',
        'accepted_ip',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];
}
