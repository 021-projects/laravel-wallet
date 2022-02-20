<?php

namespace O21\LaravelWallet\Observers;

use O21\LaravelWallet\Contracts\TransactionContract;
use O21\LaravelWallet\Contracts\TransactionObserverContract;
use O21\LaravelWallet\Events\TransactionCreated;
use O21\LaravelWallet\Events\TransactionStatusChanged;

class TransactionObserver implements TransactionObserverContract
{
    /**
     * @param  \O21\LaravelWallet\Contracts\TransactionContract  $transaction
     * @return void
     */
    public function creating(TransactionContract $transaction): void
    {
        $transaction->total = bcsub($transaction->amount, $transaction->commission, 8);
    }

    /**
     * @param  \O21\LaravelWallet\Contracts\TransactionContract  $transaction
     * @return void
     */
    public function created(TransactionContract $transaction): void
    {
        event(new TransactionCreated($transaction));
    }

    /**
     * @param  \O21\LaravelWallet\Contracts\TransactionContract  $transaction
     * @return void
     */
    public function updated(TransactionContract $transaction): void
    {
        if ($transaction->wasChanged('status')) {
            event(new TransactionStatusChanged($transaction));
        }
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
