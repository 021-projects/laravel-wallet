<?php

namespace O21\LaravelWallet\Listeners;

use O21\LaravelWallet\Events\TransactionCreated;
use O21\LaravelWallet\Events\TransactionStatusChanged;

class TransactionTriggerListener
{
    public function onTransactionCreated(TransactionCreated $event): void
    {
        $transaction = $event->getTransaction();

        if ($transaction->isCompleted()) {
            $transaction->handler()->completed();
        }
    }

    public function onTransactionStatusChanged(TransactionStatusChanged $event): void
    {
        $handler = $event->getTransaction()->handler();

        switch ($event->getType()) {
            case TransactionStatusChanged::TYPE_COMPLETED:
                $handler->completed();
                break;

            case TransactionStatusChanged::TYPE_REJECTED:
                $handler->rejected();
                break;

            case TransactionStatusChanged::TYPE_FROZEN:
                $handler->frozen();
                break;
        }
    }
}
