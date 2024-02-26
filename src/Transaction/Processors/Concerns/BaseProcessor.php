<?php

namespace O21\LaravelWallet\Transaction\Processors\Concerns;

use O21\LaravelWallet\Contracts\Transaction;

trait BaseProcessor
{
    public function __construct(protected Transaction $tx)
    {
    }

    public function prepareMeta(array $meta): array
    {
        return $meta;
    }
}
