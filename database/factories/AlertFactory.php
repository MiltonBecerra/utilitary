<?php

namespace Database\Factories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->randomDigitNotNull,
        'guest_id' => $this->faker->word,
        'exchange_source_id' => $this->faker->randomDigitNotNull,
        'target_price' => $this->faker->word,
        'condition' => $this->faker->word,
        'channel' => $this->faker->word,
        'contact_detail' => $this->faker->word,
        'status' => $this->faker->word,
        'frequency' => $this->faker->word
        ];
    }
}
