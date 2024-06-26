<?php

namespace O21\LaravelWallet\Transaction\Processors;

use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Transaction\Processors\Concerns\BaseProcessor;
use O21\LaravelWallet\Transaction\Processors\Concerns\BatchSync;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialSuccess;

class ConversionDebitProcessor implements InitialSuccess, TransactionProcessor
{
    use BaseProcessor, BatchSync;
}
