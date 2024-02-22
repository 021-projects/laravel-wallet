<?php

namespace O21\LaravelWallet\Exception;

class ImplicitTransactionMergeAttemptException extends \RuntimeException
{
    public function __construct(int $batchId)
    {
        parent::__construct(
            "Implicit attempt to add a transaction to an existing batch [{$batchId}]."
            . " Use batch({$batchId}, exists: true) if you want to prevent this error."
        );
    }
}
