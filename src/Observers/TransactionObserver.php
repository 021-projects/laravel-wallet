<?php

namespace O21\LaravelWallet\Observers;

use O21\LaravelWallet\Contracts\TransactionContract;
use O21\LaravelWallet\Contracts\TransactionObserverContract;

class TransactionObserver implements TransactionObserverContract
{
    /**
     * @param  \O21\LaravelWallet\Contracts\TransactionContract  $transaction
     * @return void
     */
    public function creating(TransactionContract $transaction): void
    {
        $transaction->total = $transaction->amount - $transaction->commission;
    }

    /**
     * @param  \O21\LaravelWallet\Contracts\TransactionContract  $transaction
     * @return void
     */
    public function saved(TransactionContract $transaction): void
    {
        optional($transaction->getUserBalance())
            ->recalculate();
    }

    /**
     * @param  \O21\LaravelWallet\Contracts\TransactionContract  $transaction
     * @return void
     */
    public function deleted(TransactionContract $transaction): void
    {
        optional($transaction->getUserBalance())
            ->recalculate();
    }
}
