<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface BalanceContract
{
    public function recalculate(): bool;

    public function User(): BelongsTo;
}
