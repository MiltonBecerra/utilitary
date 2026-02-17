<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_id',
        'utility_id',
        'public_token',
        'contact_email',
        'contact_phone',
        'channel',
        'url',
        'title',
        'store',
        'image_url',
        'current_price',
        'public_price',
        'cmr_price',
        'price_type',
        'target_price',
        'notify_on_any_drop',
        'frequency',
        'last_notified_price',
        'last_notified_at',
        'recurring_window_started_at',
        'recurring_popup_pending',
        'status',
        'last_checked_at',
        'plan_deactivated',
        'plan_deactivated_from_status',
    ];

    protected $casts = [
        'notify_on_any_drop' => 'boolean',
        'current_price' => 'decimal:2',
        'public_price' => 'decimal:2',
        'cmr_price' => 'decimal:2',
        'target_price' => 'decimal:2',
        'last_notified_price' => 'decimal:2',
        'last_notified_at' => 'datetime',
        'frequency' => 'string',
        'recurring_window_started_at' => 'datetime',
        'recurring_popup_pending' => 'boolean',
        'last_checked_at' => 'datetime',
        'plan_deactivated' => 'boolean',
        'plan_deactivated_from_status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function priceHistories()
    {
        return $this->hasMany(OfferPriceHistory::class);
    }

    public function utility()
    {
        return $this->belongsTo(Utility::class);
    }

    /**
     * Limpiar entidades HTML y stripslashes al obtener el título
     */
    public function getTitleAttribute($value)
    {
        if ($value) {
            // Primero decodificar entidades HTML, luego aplicar stripslashes
            $cleaned = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return stripslashes($cleaned);
        }
        return $value;
    }

    /**
     * Limpiar entidades HTML al guardar el título
     */
    public function setTitleAttribute($value)
    {
        if ($value) {
            $this->attributes['title'] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } else {
            $this->attributes['title'] = $value;
        }
    }
}
