<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read \O21\Numeric\Numeric $sent
 * @property-read \O21\Numeric\Numeric $received
 * @property-read \O21\LaravelWallet\Contracts\Payable $payable
 * @property string $currency
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface Balance
{
    public function recalculate(): bool;

    public function logState(?Transaction $tx = null): void;

    public function equals(string|float|int $value): bool;

    public function greaterThan(string|float|int $value): bool;

    public function greaterThanOrEqual(string|float|int $value): bool;

    public function lessThan(string|float|int $value): bool;

    public function lessThanOrEqual(string|float|int $value): bool;

    public function payable(): MorphTo;
}
