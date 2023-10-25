<?php

namespace O21\LaravelWallet\Transaction\Processors\Concerns;

use O21\LaravelWallet\Contracts\Transaction;

trait Events
{
    protected function callProcessorMethodIfExist(
        Transaction $transaction,
        string $method,
        array $arguments = []
    ): void {
        if (! $transaction->processor) {
            return;
        }

        if (method_exists($transaction->processor, $method)) {
            $transaction->processor->{$method}(...$arguments);
        }
    }
}
