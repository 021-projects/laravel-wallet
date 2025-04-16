<?php

namespace O21\LaravelWallet\Exception;

use RuntimeException;

class TxCreationRequireEventDispatcher extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Transaction creation requires event dispatcher. Do not use it inside `withoutEvents` closure.',
        );
    }
}
