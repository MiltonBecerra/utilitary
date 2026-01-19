<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmcPurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'query_id',
        'store',
        'store_label',
        'title',
        'url',
        'image_url',
        'quantity',
        'unit',
        'price',
        'card_price',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(SmcPurchase::class, 'purchase_id');
    }
}
