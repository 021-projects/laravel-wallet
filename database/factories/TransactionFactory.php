<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $amount = $this->faker->randomFloat();

        return [
            'user_id' => $this->faker->randomDigitNotZero(),
            'currency' => $this->faker->currencyCode(),
            'amount' => $amount,
            'commission' => $this->faker->randomFloat(8, 0, $amount - 1),
            'handler' => $this->faker->randomElement(config('wallet.handlers'))
        ];
    }
}
