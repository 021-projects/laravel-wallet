<?php

namespace O21\LaravelWallet\Events;

use O21\LaravelWallet\Contracts\TransactionContract;

class TransactionCreated
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        protected TransactionContract $transaction
    ) { }

    /**
     * @return \O21\LaravelWallet\Contracts\TransactionContract
     */
    public function getTransaction(): TransactionContract
    {
        return $this->transaction;
    }
}
