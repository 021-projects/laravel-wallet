<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use O21\LaravelWallet\Models\Balance;

class BalanceFactory extends Factory
{
    protected $model = Balance::class;

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
