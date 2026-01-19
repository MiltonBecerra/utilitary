<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmcPurchase extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'query_text',
        'location',
        'queries',
        'name',
        'stores',
        'items_count',
        'totals',
    ];

    protected $casts = [
        'totals' => 'array',
        'queries' => 'array',
        'stores' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SmcPurchaseItem::class, 'purchase_id');
    }

    public function getLabelAttribute(): string
    {
        $name = trim((string) ($this->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $date = optional($this->created_at)->format('Y-m-d H:i') ?? 'Sin fecha';
        $count = (int) ($this->items_count ?? 0);
        return "Compra {$date} ({$count})";
    }
}
