<?php

namespace O21\LaravelWallet\Contracts;

interface TransactionObserverContract
{
    public function creating(TransactionContract $transaction): void;

    public function saved(TransactionContract $transaction): void;

    public function deleted(TransactionContract $transaction): void;
}
