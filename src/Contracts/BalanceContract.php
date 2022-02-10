<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $currency
 */
interface BalanceContract
{
    public function recalculate(): bool;

    public function User(): BelongsTo;
}
