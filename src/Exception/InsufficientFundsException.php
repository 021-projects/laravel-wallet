<?php

namespace O21\LaravelWallet\Exception;

use Exception;
use O21\LaravelWallet\Contracts\Payable;

class InsufficientFundsException extends Exception
{
    public static function assertFails(
        Payable $payable,
        string $needs,
        string $currency
    ): static {
        return new static("The Payable [{$payable->getKey()}] does not have enough funds. Needs: $needs $currency");
    }
}
