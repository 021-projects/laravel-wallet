<?php

namespace O21\LaravelWallet\Listeners;

use Illuminate\Events\Dispatcher;
use O21\LaravelWallet\Contracts\Balance;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Events\TransactionCreated;
use O21\LaravelWallet\Events\TransactionDeleted;
use O21\LaravelWallet\Events\TransactionStatusChanged;
use O21\LaravelWallet\Events\TransactionUpdated;
use O21\LaravelWallet\Transaction\Processors\Concerns\Events;

class TransactionEventsSubscriber
{
    use Events;

    public function onTransactionCreated(TransactionCreated $event): void
    {
        $tx = $event->transaction;
        $this->callProcessorMethodIfExist($tx, 'created');

        if (config('wallet.balances.log_states')) {
            $this->logBalanceStates($tx);
        }
    }

    protected function logBalanceStates(Transaction $tx): void
    {
        $balanceClass = app(Balance::class);
        if (! method_exists($balanceClass, 'logState')) {
            return;
        }

        $tx->from?->balance($tx->currency)?->logState($tx);
        $tx->to?->balance($tx->currency)?->logState($tx);
    }

    public function onTransactionUpdated(TransactionUpdated $event): void
    {
        $this->callProcessorMethodIfExist($event->transaction, 'updated');
    }

    public function onTransactionDeleted(TransactionDeleted $event): void
    {
        $this->callProcessorMethodIfExist($event->transaction, 'deleted');
    }

    public function onTransactionStatusChanged(TransactionStatusChanged $event): void
    {
        $this->callProcessorMethodIfExist($event->transaction, 'statusChanged', [
            $event->transaction->status,
            $event->oldStatus
        ]);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            TransactionCreated::class,
            [self::class, 'onTransactionCreated']
        );

        $events->listen(
            TransactionUpdated::class,
            [self::class, 'onTransactionUpdated']
        );

        $events->listen(
            TransactionDeleted::class,
            [self::class, 'onTransactionDeleted']
        );

        $events->listen(
            TransactionStatusChanged::class,
            [self::class, 'onTransactionStatusChanged']
        );
    }
}
