<?php

namespace O21\LaravelWallet\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;

class DepositProcessor implements TransactionProcessor
{
    use Concerns\BaseProcessor;
    use Concerns\PositiveAmount;
    use Concerns\InitialSuccess;
}
