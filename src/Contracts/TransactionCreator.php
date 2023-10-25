<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use O21\LaravelWallet\Numeric;

interface TransactionCreator
{
    public function commit(): Transaction;
    public function before(callable $before): self;
    public function after(callable $after): self;
    public function amount(string|float|int|Numeric $amount): self;
    public function currency(string $currency): self;
    public function commission(string|float|int|Numeric $commission): self;
    public function processor(string $processor): self;
    public function user(SupportsBalance $user): self;
    public function to(SupportsBalance $user): self; // alias for user()
    public function from(SupportsBalance $user): self; // alias for user()
    public function meta(array $meta): self;
    public function lockOnRecord(Model|Builder $lockRecord): self;
}
