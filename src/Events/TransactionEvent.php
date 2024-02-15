<?php

namespace O21\LaravelWallet\Events;

use O21\LaravelWallet\Contracts\Transaction;

abstract class TransactionEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Transaction $transaction
    ) {
    }
}
