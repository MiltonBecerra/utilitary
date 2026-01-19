<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_alert_id',
        'price',
        'checked_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'checked_at' => 'datetime',
    ];

    public function offerAlert()
    {
        return $this->belongsTo(OfferAlert::class);
    }
}
