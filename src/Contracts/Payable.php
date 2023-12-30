<?php

namespace O21\LaravelWallet\Contracts;

interface Payable
{
    public function balance(?string $currency = null): Balance;

    public function assertHaveFunds(string $needs, ?string $currency = null): void;

    public function isEnoughFunds(string $needs, ?string $currency = null): bool;

    public function getMorphClass();

    public function getKey();
}
