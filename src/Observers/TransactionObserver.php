<?php

namespace O21\LaravelWallet\Observers;

use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionPreparer;
use O21\LaravelWallet\Events\TransactionCreated;
use O21\LaravelWallet\Events\TransactionDeleted;
use O21\LaravelWallet\Events\TransactionStatusChanged;
use O21\LaravelWallet\Events\TransactionUpdated;
use O21\LaravelWallet\Transaction\Processors\Concerns\Events;

class TransactionObserver
{
    use Events;

    public function saved(Transaction $transaction): void
    {
        $transaction->getRelatedBalance()?->recalculate();
    }

    public function creating(Transaction $transaction): void
    {
        $this->callProcessorMethodIfExist($transaction, 'creating');
    }

    public function created(Transaction $transaction): void
    {
        event(new TransactionCreated($transaction));
    }

    public function updating(Transaction $transaction): void
    {
        $this->callProcessorMethodIfExist($transaction, 'updating');
    }

    public function updated(Transaction $transaction): void
    {
        if ($transaction->wasChanged('status')) {
            event(new TransactionStatusChanged(
                $transaction,
                $transaction->getOriginal('status')
            ));
        }

        event(new TransactionUpdated($transaction));
    }

    public function deleting(Transaction $transaction): void
    {
        $this->callProcessorMethodIfExist($transaction, 'deleting');
    }

    public function deleted(Transaction $transaction): void
    {
        event(new TransactionDeleted($transaction));
    }
}
