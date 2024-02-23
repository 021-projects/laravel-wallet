<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface Payable
{
    public function balance(?string $currency = null): Balance;

    public function balanceStates(): MorphMany;

    public function assertHaveFunds(string $needs, ?string $currency = null): void;

    public function isEnoughFunds(string $needs, ?string $currency = null): bool;

    public function getMorphClass();

    public function getKey();
}
