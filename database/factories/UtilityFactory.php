<?php

namespace Database\Factories;

use App\Models\Utility;
use Illuminate\Database\Eloquent\Factories\Factory;

class UtilityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Utility::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
        'slug' => $this->faker->word,
        'description' => $this->faker->text,
        'icon' => $this->faker->word,
        'is_active' => $this->faker->word
        ];
    }
}
