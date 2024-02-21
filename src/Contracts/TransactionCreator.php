<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use O21\LaravelWallet\Numeric;

interface TransactionCreator
{
    public function commit(): Transaction;

    public function amount(string|float|int|Numeric $amount): self;

    public function currency(string $currency): self;

    public function commission(string|float|int|Numeric $commission): self;

    public function status(string $status): self;

    public function setDefaultStatus(): self;

    public function processor(string $processor): self;

    public function from(Payable $payable): self;

    public function to(Payable $payable): self;

    public function meta(array $meta): self;

    public function lockOnRecord(Model|Builder|bool $lockRecord): self;

    public function before(callable $before): self;

    public function after(callable $after): self;

    public function overcharge(bool $allow = true): self;
}
