<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use O21\LaravelWallet\Numeric;

interface TransactionCreator
{
    public function commit(): Transaction;

    public function model(): Transaction;

    public function amount(string|float|int|Numeric $amount): self;

    public function currency(string $currency): self;

    public function commission(string|float|int|Numeric $value, ...$opts): self;

    public function status(string $status): self;

    public function setDefaultStatus(): self;

    public function processor(string $processor): self;

    public function from(Payable $payable): self;

    public function to(Payable $payable): self;

    public function meta(array $meta): self;

    public function invisible(bool $invisible = true): self;

    public function lockOnRecord(Model|Builder|bool $lockRecord): self;

    public function before(callable $callback): self;

    public function after(callable $callback): self;

    public function overcharge(bool $allow = true): self;

    public function batch(int $id, ?bool $exists = null): self;
}
