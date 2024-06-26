<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use O21\Numeric\Numeric;

interface Converter
{
    /**
     * @return \Illuminate\Support\Collection<\O21\LaravelWallet\Contracts\Transaction>
     */
    public function performOn(Payable $payable): Collection;

    public function amount(string|float|int|Numeric $amount): self;

    public function commission(
        string|float|int|Numeric $src = 0,
        string|float|int|Numeric $dest = 0,
    ): self;

    public function from(string $currency): self;

    public function to(string $currency): self;

    public function at(string|float|int|Numeric $rate): self;

    public function meta(array $meta): self;

    public function lockOnRecord(Model|Builder|bool $lockRecord): self;

    public function before(callable $callback): self;

    public function after(callable $callback): self;

    public function overcharge(bool $allow = true): self;

    public function batch(int $id, ?bool $exists = null): self;
}
