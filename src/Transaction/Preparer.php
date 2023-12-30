<?php

namespace O21\LaravelWallet\Transaction;

use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionPreparer;

class Preparer implements TransactionPreparer
{
    public function prepare(Transaction $tx): void
    {
        $tx->received = num($tx->amount)->sub($tx->commission)->get();
    }
}
