<?php

namespace O21\LaravelWallet\Models\Concerns;

use O21\LaravelWallet\Contracts\Balance;
use O21\LaravelWallet\Exception\InsufficientFundsException;

trait HasBalance
{
    protected array $_balances = [];

    public function getBalance(?string $currency = null): Balance
    {
        $currency ??= config('wallet.default_currency');

        if (! isset($this->_balances[$currency])) {
            $attributes = [
                'user_id'  => $this->getAuthIdentifier(),
                'currency' => $currency
            ];

            $balanceClass = app(Balance::class);
            $this->cacheBalanceModel($balanceClass::firstOrCreate($attributes));
        }

        return $this->_balances[$currency];
    }

    public function assertHaveFunds(
        string $needs,
        ?string $currency = null
    ): void {
        if (! $this->isEnoughFunds($needs, $currency)) {
            throw InsufficientFundsException::assertFails($this, $needs, $currency);
        }
    }

    public function isEnoughFunds(
        string $needs,
        ?string $currency = null
    ): bool {
        return $this->getBalance($currency)->greaterThanOrEqual(num($needs)->positive());
    }

    protected function cacheBalanceModel(Balance $balance): void
    {
        $this->_balances[$balance->currency] = $balance;
    }
}
