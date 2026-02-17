<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppContact extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'normalized_phone',
        'raw_phone',
        'user_id',
        'guest_id',
        'first_source',
        'first_prompted_at',
    ];

    protected $casts = [
        'first_prompted_at' => 'datetime',
    ];
}
