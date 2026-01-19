<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityPayment extends Model
{
    protected $fillable = [
        'uuid',
        'utility_id',
        'utility_slug',
        'user_id',
        'guest_id',
        'plan',
        'currency',
        'amount',
        'status',
        'mp_preference_id',
        'mp_payment_id',
        'mp_status',
        'mp_status_detail',
        'mp_payment_type',
        'mp_response',
        'subscription_id',
    ];

    protected $casts = [
        'mp_response' => 'array',
    ];
}
