<?php

namespace O21\LaravelWallet\Transaction\Processors\Concerns;

trait NegativeAmount
{
    public function prepareAmount(string $amount): string
    {
        return num($amount)->negative();
    }
}
