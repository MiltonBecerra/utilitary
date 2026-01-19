<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Alert
 * @package App\Models
 * @version December 5, 2025, 6:47 pm UTC
 *
 * @property integer $user_id
 * @property string $guest_id
 * @property integer $exchange_source_id
 * @property number $target_price
 * @property string $condition
 * @property string $channel
 * @property string $contact_detail
 * @property string $status
 * @property string $frequency
 */
class Alert extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'alerts';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'user_id',
        'guest_id',
        'exchange_source_id',
        'target_price',
        'condition',
        'channel',
        'contact_detail',
        'contact_phone',
        'status',
        'frequency',
        'last_notified_at',
        'daily_notified_date',
        'daily_notified_count',
        'plan_deactivated',
        'plan_deactivated_from_status'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'guest_id' => 'string',
        'exchange_source_id' => 'integer',
        'target_price' => 'decimal:3',
        'condition' => 'string',
        'channel' => 'string',
        'contact_detail' => 'string',
        'contact_phone' => 'string',
        'status' => 'string',
        'frequency' => 'string',
        'last_notified_at' => 'datetime',
        'daily_notified_date' => 'date',
        'daily_notified_count' => 'integer',
        'plan_deactivated' => 'boolean',
        'plan_deactivated_from_status' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'nullable|exists:users,id',
        'guest_id' => 'nullable',
        'exchange_source_id' => 'required|exists:exchange_sources,id',
        'target_price' => 'required|numeric',
        'condition' => 'required|in:above,below',
        'channel' => 'required|in:email,whatsapp',
        'contact_detail' => 'required',
        'status' => 'required|in:active,inactive,triggered,fallback_email',
        'frequency' => 'required|in:once,recurring,recurring_paused'
    ];

    public function getFrequencyAttribute($value)
    {
        return $value === 'recurring_paused' ? 'once' : $value;
    }

    public function exchangeSource()
    {
        return $this->belongsTo(\App\Models\ExchangeSource::class, 'exchange_source_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
