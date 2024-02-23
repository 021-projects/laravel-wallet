<?php

namespace O21\LaravelWallet\Exception;

use RuntimeException;

class UnknownTxProcessorException extends RuntimeException
{
    public function __construct(?string $processorId = null)
    {
        $processorId = $processorId ?? 'null';
        parent::__construct("Processor with id `{$processorId}` was not registered.");
    }
}
