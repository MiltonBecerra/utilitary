<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Utility
 * @package App\Models
 * @version December 5, 2025, 6:45 pm UTC
 *
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $icon
 * @property boolean $is_active
 */
class Utility extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'utilities';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'description' => 'string',
        'icon' => 'string',
        'is_active' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|unique:utilities',
        'slug' => 'required|unique:utilities',
        'description' => 'nullable',
        'icon' => 'nullable',
        'is_active' => 'required'
    ];

    public function comments()
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    public function subscriptions()
    {
        return $this->belongsToMany(\App\Models\Subscription::class, 'subscription_utility');
    }
}
