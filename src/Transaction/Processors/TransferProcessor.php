<?php

namespace O21\LaravelWallet\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialSuccess;

class TransferProcessor implements InitialSuccess, TransactionProcessor
{
    use Concerns\BaseProcessor;
}
