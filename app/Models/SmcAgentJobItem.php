<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmcAgentJobItem extends Model
{
    protected $fillable = [
        'job_id',
        'store',
        'store_label',
        'title',
        'url',
        'quantity',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(SmcAgentJob::class, 'job_id');
    }
}
