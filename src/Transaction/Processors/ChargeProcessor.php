<?php

namespace O21\LaravelWallet\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialSuccess;

class ChargeProcessor implements InitialSuccess, TransactionProcessor
{
    use Concerns\BaseProcessor;
}
