<?php

namespace O21\LaravelWallet\Concerns;

use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Exception\ImplicitTxMergeAttemptException;

trait Batchable
{
    protected ?int $batchId = null;

    protected bool $batchExists = false;

    public function batch(int $id, ?bool $exists = null): self
    {
        $this->batchId = $id;

        if ($exists !== null) {
            $this->batchExists = $exists;
        }

        return $this;
    }

    protected function nextBatch(): int
    {
        return $this->batchId ?? app(Transaction::class)->nextBatch();
    }

    protected function validateBatch(int $batch): void
    {
        throw_if(
            ! $this->batchExists && app(Transaction::class)->where('batch', $batch)->exists(),
            ImplicitTxMergeAttemptException::class,
            $batch
        );
    }
}
