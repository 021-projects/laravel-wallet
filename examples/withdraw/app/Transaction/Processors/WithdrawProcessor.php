<?php

namespace App\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Transaction\Processors\Concerns\BaseProcessor;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialSuccess;

class WithdrawProcessor implements TransactionProcessor, InitialSuccess
{
    use BaseProcessor;
}
