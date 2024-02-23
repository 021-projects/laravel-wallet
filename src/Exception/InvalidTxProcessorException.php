<?php

namespace O21\LaravelWallet\Exception;

use O21\LaravelWallet\Contracts\TransactionProcessor;
use RuntimeException;

class InvalidTxProcessorException extends RuntimeException
{
    public function __construct(string $processorId)
    {
        parent::__construct(
            "Processor `$processorId` is invalid. "
            ."It should be an instance of ".TransactionProcessor::class
        );
    }
}
