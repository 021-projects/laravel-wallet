<?php

namespace O21\LaravelWallet\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;

class ChargeProcessor implements TransactionProcessor
{
    use Concerns\BaseProcessor;
    use Concerns\NegativeAmount;
    use Concerns\InitialSuccess;
}
