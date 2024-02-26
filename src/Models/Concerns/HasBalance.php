<?php

namespace O21\LaravelWallet\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use O21\LaravelWallet\Contracts\Balance;
use O21\LaravelWallet\Exception\InsufficientFundsException;

use function O21\LaravelWallet\ConfigHelpers\default_currency;
use function O21\LaravelWallet\ConfigHelpers\get_model_class;

trait HasBalance
{
    protected array $_balances = [];

    public function balance(?string $currency = null): Balance
    {
        $currency ??= default_currency();

        if (! isset($this->_balances[$currency])) {
            $attributes = [
                'payable_type' => $this->getMorphClass(),
                'payable_id' => $this->getKey(),
                'currency' => $currency,
            ];

            $balanceClass = app(Balance::class);
            $this->cacheBalanceModel($balanceClass::firstOrCreate($attributes));
        }

        return $this->_balances[$currency];
    }

    public function balanceStates(): MorphMany
    {
        return $this->morphMany(
            get_model_class('balance_state'),
            'payable'
        );
    }

    public function assertHaveFunds(
        string $needs,
        ?string $currency = null
    ): void {
        // Ensure that the balance is up-to-date
        $this->balance($currency)->refresh();

        if (! $this->isEnoughFunds($needs, $currency)) {
            throw InsufficientFundsException::assertFails($this, $needs, $currency);
        }
    }

    public function isEnoughFunds(
        string $needs,
        ?string $currency = null
    ): bool {
        return $this->balance($currency)->greaterThanOrEqual(num($needs)->positive());
    }

    protected function cacheBalanceModel(Balance $balance): void
    {
        $this->_balances[$balance->currency] = $balance;
    }
}
