<?php

namespace O21\LaravelWallet\Contracts;

interface TransactionHandlerContract
{
    public function getData(): array;

    public function setTransaction(TransactionContract $transaction): self;

    public function completed();

    public function rejected();

    public function frozen();

    public function validAmount(): string;
}
