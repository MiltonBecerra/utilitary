<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ExchangeRate
 * @package App\Models
 * @version December 5, 2025, 6:46 pm UTC
 *
 * @property integer $exchange_source_id
 * @property number $buy_price
 * @property number $sell_price
 * @property string $currency_from
 * @property string $currency_to
 */
class ExchangeRate extends Model
{
    use SoftDeletes;

    use HasFactory;

    public $table = 'exchange_rates';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'exchange_source_id',
        'buy_price',
        'sell_price',
        'currency_from',
        'currency_to'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'exchange_source_id' => 'integer',
        'buy_price' => 'decimal:3',
        'sell_price' => 'decimal:3',
        'currency_from' => 'string',
        'currency_to' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'exchange_source_id' => 'required|exists:exchange_sources,id',
        'buy_price' => 'required|numeric',
        'sell_price' => 'required|numeric',
        'currency_from' => 'required',
        'currency_to' => 'required'
    ];

    public function exchangeSource()
    {
        return $this->belongsTo(\App\Models\ExchangeSource::class, 'exchange_source_id');
    }
}
