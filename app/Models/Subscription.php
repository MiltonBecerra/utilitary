<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Subscription
 * @package App\Models
 * @version December 5, 2025, 6:47 pm UTC
 *
 * @property integer $user_id
 * @property string $plan_type
 * @property string $starts_at
 * @property string $ends_at
 */
class Subscription extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'subscriptions';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'user_id',
        'guest_id',
        'plan_type',
        'starts_at',
        'ends_at'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'plan_type' => 'string',
        'starts_at' => 'date',
        'ends_at' => 'date'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'plan_type' => 'required|in:free,basic,pro',
        'starts_at' => 'required',
        'ends_at' => 'required'
    ];

    public static $createRules = [
        'user_id' => 'required_without:guest_id|exists:users,id',
        'guest_id' => 'required_without:user_id|string',
        'plan_type' => 'required|in:free,basic,pro',
        'starts_at' => 'required',
        'ends_at' => 'required'
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function utilities()
    {
        return $this->belongsToMany(\App\Models\Utility::class, 'subscription_utility');
    }

    /**
     * Check if the subscription is currently active.
     * 
     * @return bool
     */
    public function isActive()
    {
        return $this->ends_at->toDateString() >= now()->toDateString();
    }

    /**
     * Check if the subscription has expired.
     * 
     * @return bool
     */
    public function isExpired()
    {
        return $this->ends_at->toDateString() < now()->toDateString();
    }

    /**
     * Check if the subscription is of a specific plan type.
     * 
     * @param string $planType
     * @return bool
     */
    public function isPlan($planType)
    {
        return $this->plan_type === $planType;
    }

    /**
     * Scope a query to only include active subscriptions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereDate('ends_at', '>=', now()->toDateString());
    }

    /**
     * Get the number of days remaining in the subscription.
     * 
     * @return int
     */
    public function daysRemaining()
    {
        if ($this->isExpired()) {
            return 0;
        }
        return now()->diffInDays($this->ends_at);
    }

    /**
     * Check if this is a guest subscription.
     * 
     * @return bool
     */
    public function isGuestSubscription()
    {
        return !empty($this->guest_id);
    }

    /**
     * Scope a query to only include guest subscriptions.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $guestId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForGuest($query, $guestId)
    {
        return $query->where('guest_id', $guestId);
    }
}
