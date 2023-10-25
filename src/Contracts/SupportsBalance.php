<?php

namespace O21\LaravelWallet\Contracts;

interface SupportsBalance
{
    public function getBalance(?string $currency = null): Balance;

    public function assertHaveFunds(string $needs, ?string $currency = null): void;

    public function isEnoughFunds(string $needs, ?string $currency = null): bool;

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier();
}
