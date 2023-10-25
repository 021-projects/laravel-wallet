<?php

namespace O21\LaravelWallet\Transaction\Processors\Concerns;

use O21\LaravelWallet\Enums\TransactionStatus;

trait InitialSuccess
{
    public function creating(): void
    {
        $this->transaction->status = TransactionStatus::SUCCESS;
    }
}
