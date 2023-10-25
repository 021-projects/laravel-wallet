<?php

namespace O21\LaravelWallet\Listeners;

use Illuminate\Events\Dispatcher;
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
        $this->callProcessorMethodIfExist($event->transaction, 'created');
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
