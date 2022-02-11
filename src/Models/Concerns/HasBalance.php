<?php

namespace O21\LaravelWallet\Models\Concerns;

use O21\LaravelWallet\Contracts\BalanceContract;

trait HasBalance
{
    protected array $balances = [];

    public function getBalance(?string $currency = null): BalanceContract
    {
        if (! $currency) {
            $currency = config('wallet.currencies.basic');
        }

        if (! isset($this->balances[$currency])) {
            $attributes = [
                'user_id'  => $this->id,
                'currency' => $currency
            ];

            $balanceClass = app(BalanceContract::class);
            $this->setBalanceCached($balanceClass::firstOrCreate($attributes));
        }

        return $this->balances[$currency];
    }

    public function setBalanceCached(BalanceContract $balance): void
    {
        $this->balances[$balance->currency] = $balance;
    }
}
