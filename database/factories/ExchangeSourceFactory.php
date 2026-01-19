<?php

namespace Database\Factories;

use App\Models\ExchangeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeSourceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExchangeSource::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
        'url' => $this->faker->word,
        'selector_buy' => $this->faker->word,
        'selector_sell' => $this->faker->word,
        'is_active' => $this->faker->word
        ];
    }
}
