<?php

namespace O21\LaravelWallet\Observers;

use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Events\TransactionCreated;
use O21\LaravelWallet\Events\TransactionDeleted;
use O21\LaravelWallet\Events\TransactionStatusChanged;
use O21\LaravelWallet\Events\TransactionUpdated;
use O21\LaravelWallet\Transaction\Processors\Concerns\Events;

class TransactionObserver
{
    use Events;

    public function saved(Transaction $tx): void
    {
        $tx->recalculateBalances();
    }

    public function creating(Transaction $tx): void
    {
        $this->callProcessorMethodIfExist($tx, 'creating');
    }

    public function created(Transaction $tx): void
    {
        event(new TransactionCreated($tx));
    }

    public function updating(Transaction $tx): void
    {
        $tx->normalizeNumbers();

        $this->callProcessorMethodIfExist($tx, 'updating');
    }

    public function updated(Transaction $tx): void
    {
        if ($tx->wasChanged('status')) {
            $originalStatus = $tx->getOriginal('status');
            event(new TransactionStatusChanged($tx, $originalStatus));
        }

        event(new TransactionUpdated($tx));
    }

    public function deleting(Transaction $tx): void
    {
        $this->callProcessorMethodIfExist($tx, 'deleting');
    }

    public function deleted(Transaction $tx): void
    {
        $tx->recalculateBalances();

        event(new TransactionDeleted($tx));
    }
}
