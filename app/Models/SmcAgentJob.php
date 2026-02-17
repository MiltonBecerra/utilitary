<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmcAgentJob extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'guest_id',
        'device_id',
        'store',
        'status',
        'items',
        'progress',
        'result',
        'error_message',
        'locked_at',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'items' => 'array',
        'progress' => 'array',
        'result' => 'array',
        'locked_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function jobItems(): HasMany
    {
        return $this->hasMany(SmcAgentJobItem::class, 'job_id');
    }
}
