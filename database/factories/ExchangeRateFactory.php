<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeRateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'exchange_source_id' => $this->faker->randomDigitNotNull,
        'buy_price' => $this->faker->word,
        'sell_price' => $this->faker->word,
        'currency_from' => $this->faker->word,
        'currency_to' => $this->faker->word
        ];
    }
}
