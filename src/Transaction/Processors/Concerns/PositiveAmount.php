<?php

namespace O21\LaravelWallet\Transaction\Processors\Concerns;

trait PositiveAmount
{
    public function prepareAmount(string $amount): string
    {
        return num($amount)->positive();
    }
}
