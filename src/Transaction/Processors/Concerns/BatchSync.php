<?php

namespace O21\LaravelWallet\Transaction\Processors\Concerns;

use O21\LaravelWallet\Contracts\Transaction;

trait BatchSync
{
    public function statusChanged(string $status, string $oldStatus): void
    {
        $this->tx->neighbours->each(function (Transaction $tx) use ($status) {
            if ($tx->status === $status) {
                return;
            }

            $tx->updateStatus($status);
        });
    }
}
