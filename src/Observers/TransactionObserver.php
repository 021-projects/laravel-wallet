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

    public function saved(Transaction $tx): void
    {
        $this->recalculateBalances($tx);
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
        $this->callProcessorMethodIfExist($tx, 'updating');

        if ($tx->isDirty('amount')) {
            /** @var \O21\LaravelWallet\Transaction\Preparer $preparer */
            $preparer = app(TransactionPreparer::class);
            $preparer->prepare($tx);
        }
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
        $this->recalculateBalances($tx);

        event(new TransactionDeleted($tx));
    }

    protected function recalculateBalances(Transaction $tx): void
    {
        // backward compatibility
        // TODO: add recalculateBalances method to Transaction interface in next major release
        if (method_exists($tx, 'recalculateBalances')) {
            $tx->recalculateBalances();

            return;
        }

        if ($tx->wasChanged('currency')) {
            $oldCurrency = $tx->getOriginal('currency');
            $tx->from?->balance($oldCurrency)?->recalculate();
            $tx->to?->balance($oldCurrency)?->recalculate();
        }

        $tx->from?->balance($tx->currency)?->recalculate();
        $tx->to?->balance($tx->currency)?->recalculate();
    }
}
