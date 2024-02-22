<?php

namespace O21\LaravelWallet\Observers;

use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionPreparer;
use O21\LaravelWallet\Events\TransactionCreated;
use O21\LaravelWallet\Events\TransactionDeleted;
use O21\LaravelWallet\Events\TransactionStatusChanged;
use O21\LaravelWallet\Events\TransactionUpdated;
use O21\LaravelWallet\Transaction\Processors\Concerns\Events;

use function O21\LaravelWallet\ConfigHelpers\currency_scale;

class TransactionObserver
{
    use Events;

    public function saving(Transaction $tx): void
    {
        $scale = currency_scale($tx->currency);
        $tx->amount = num($tx->amount)->scale($scale)->get();
        $tx->commission = num($tx->commission)->scale($scale)->get();
        $tx->received = num($tx->received)->scale($scale)->get();
    }

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
        $tx->recalculateBalances();

        event(new TransactionDeleted($tx));
    }
}
