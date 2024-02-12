<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use O21\LaravelWallet\Tests\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
