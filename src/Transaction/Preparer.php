<?php

namespace O21\LaravelWallet\Transaction;

use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionPreparer;

class Preparer implements TransactionPreparer
{
    public function prepare(Transaction $transaction): void
    {
        $transaction->amount = $transaction->processor
            ?->prepareAmount($transaction->amount)
            ?? $transaction->amount;
        $transaction->total = $transaction->getTotal();
    }
}
