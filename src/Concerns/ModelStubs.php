<?php

namespace O21\LaravelWallet\Concerns;

use O21\LaravelWallet\Contracts\SupportsBalance;
use O21\LaravelWallet\Contracts\Transaction;

trait ModelStubs
{
    protected function findUser(int $userId): ?SupportsBalance
    {
        $modelClass = config('wallet.models.user');
        return $modelClass::find($userId);
    }

    protected function findTransaction(int $transactionId): ?Transaction
    {
        $modelClass = config('wallet.models.transaction');
        return $modelClass::find($transactionId);
    }
}
