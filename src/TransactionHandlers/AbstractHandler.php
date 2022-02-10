<?php

namespace O21\LaravelWallet\TransactionHandlers;

use Illuminate\Support\Arr;
use O21\LaravelWallet\Contracts\TransactionContract;
use O21\LaravelWallet\Contracts\TransactionHandlerContract;
use O21\LaravelWallet\Contracts\UserContract;

abstract class AbstractHandler implements TransactionHandlerContract
{
    use Concerns\BalanceManipulations;

    protected TransactionContract $transaction;

    public function getData(): array
    {
        return [];
    }

    public function completed(): void
    {
    }

    public function rejected(): void
    {
    }

    public function frozen(): void
    {
    }

    public function validAmount(): float
    {
        return $this->transaction->amount;
    }

    public function getTransaction(): TransactionContract
    {
        return $this->transaction;
    }

    public function setTransaction(TransactionContract $transaction): self
    {
        $this->transaction = $transaction;
        return $this;
    }

    public function getUser(): UserContract
    {
        return $this->transaction->User;
    }

    /**
     * @param null $key
     * @return array|\ArrayAccess|mixed
     */
    public function data($key = null)
    {
        return Arr::get($this->transaction->data, $key);
    }
}
