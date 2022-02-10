<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->randomDigitNotZero(),
            'currency' => $this->faker->currencyCode(),
            'value' => $this->faker->randomFloat()
        ];
    }
}
