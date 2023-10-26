<?php

namespace O21\LaravelWallet\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialSuccess;

class DepositProcessor implements TransactionProcessor, InitialSuccess
{
    use Concerns\BaseProcessor;
    use Concerns\PositiveAmount;
}
