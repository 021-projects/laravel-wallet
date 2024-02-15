<?php

namespace O21\LaravelWallet\Tests\Concerns;

use Illuminate\Database\Eloquent\Model;
use Workbench\App\Models\User;

trait BalanceSeed
{
    protected function createBalance(): array
    {
        $this->refreshDatabase();

        /** @var \O21\LaravelWallet\Contracts\Payable $user */
        $user = User::factory()->create();
        $currency = $this->faker->currencyCode();
        /** @var \O21\LaravelWallet\Contracts\Balance $balance */
        $balance = $user->balance($currency);

        return [$user, $currency, $balance];
    }

    protected function assertBalanceRefreshEquals(
        Model $balance,
        mixed $value,
        string $valueColumn = 'value'
    ): void {
        $this->assertEquals(
            num($value)->get(),
            num($balance->refresh()->{$valueColumn})->get()
        );
    }
}
