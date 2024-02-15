<?php

namespace O21\LaravelWallet\Concerns;

use O21\LaravelWallet\Contracts\Transaction;

trait ModelStubs
{
    protected function findTransaction(int $transactionId): ?Transaction
    {
        $modelClass = config('wallet.models.transaction');

        return $modelClass::find($transactionId);
    }
}
