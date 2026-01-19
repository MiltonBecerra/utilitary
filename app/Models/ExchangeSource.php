<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ExchangeSource
 * @package App\Models
 * @version December 5, 2025, 6:45 pm UTC
 *
 * @property string $name
 * @property string $url
 * @property string $selector_buy
 * @property string $selector_sell
 * @property boolean $is_active
 */
class ExchangeSource extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'exchange_sources';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'url',
        'selector_buy',
        'selector_sell',
        'is_active'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'url' => 'string',
        'selector_buy' => 'string',
        'selector_sell' => 'string',
        'is_active' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|unique:exchange_sources',
        'url' => 'required',
        'selector_buy' => 'required',
        'selector_sell' => 'required',
        'is_active' => 'required'
    ];

    
}
