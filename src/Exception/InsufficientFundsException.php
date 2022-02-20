<?php

namespace O21\LaravelWallet\Exception;

use Exception;
use O21\LaravelWallet\Contracts\UserContract;

class InsufficientFundsException extends Exception
{
    public static function assertFails(
        UserContract $user,
        string $needs
    ): static {
        return new static("The user ($user->id) does not have enough funds. Needs: $needs");
    }
}
