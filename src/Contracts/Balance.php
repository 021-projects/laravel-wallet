<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read \O21\LaravelWallet\Numeric $sent
 * @property-read \O21\LaravelWallet\Numeric $received
 * @property-read \O21\LaravelWallet\Contracts\Payable $payable
 * @property string $currency
 */
interface Balance
{
    public function recalculate(): bool;

    public function equals(string|float|int $value): bool;
    public function greaterThan(string|float|int $value): bool;
    public function greaterThanOrEqual(string|float|int $value): bool;
    public function lessThan(string|float|int $value): bool;
    public function lessThanOrEqual(string|float|int $value): bool;

    public function payable(): MorphTo;
}
