<?php

namespace O21\LaravelWallet\Exception;

use RuntimeException;

class FromOrOverchargeRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Transaction must have "from" when overcharge is not allowed');
    }
}
