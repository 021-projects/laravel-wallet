<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use O21\LaravelWallet\Models\Transaction;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $amount = $this->faker->randomFloat(8, 0, 99_999_999);

        return [
            'user_id' => $this->faker->randomDigitNotZero(),
            'currency' => $this->faker->currencyCode(),
            'amount' => $amount,
            'commission' => $this->faker->randomFloat(8, 0, $amount - 1),
            'handler' => $this->faker->randomElement(config('wallet.handlers'))
        ];
    }
}
