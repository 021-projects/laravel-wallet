<?php

namespace O21\LaravelWallet\Models\Concerns;

use O21\LaravelWallet\Contracts\BalanceContract;
use O21\LaravelWallet\Contracts\TransactionContract;
use O21\LaravelWallet\Exception\InsufficientFundsException;

trait HasBalance
{
    protected array $balances = [];

    public function getBalance(?string $currency = null): BalanceContract
    {
        $currency ??= config('wallet.currencies.basic');

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

    public function assertHaveFunds(string $needs, ?string $currency = null): void
    {
        if (! $this->isEnoughFunds($needs, $currency)) {
            throw InsufficientFundsException::assertFails($this, $needs);
        }
    }

    public function isEnoughFunds(string $needs, ?string $currency = null): bool
    {
        return bccomp($this->getBalance($currency)->value, $needs, 8) >= 0;
    }

    public function replenish(
        string $amount,
        ?string $currency = null
    ): TransactionContract {
        $currency ??= config('wallet.currencies.basic');
        $transactionClass = app(TransactionContract::class);

        return $transactionClass::create(
            'replenishment',
            $this,
            $amount,
            $currency
        );
    }

    public function writeOff(
        string $amount,
        ?string $currency = null
    ): TransactionContract {
        $currency ??= config('wallet.currencies.basic');
        $transactionClass = app(TransactionContract::class);

        return $transactionClass::create(
            'write_off',
            $this,
            $amount,
            $currency
        );
    }
}
