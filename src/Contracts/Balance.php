<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
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

    public function User(): BelongsTo;
}
