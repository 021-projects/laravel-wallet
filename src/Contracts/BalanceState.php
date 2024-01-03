<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * O21\LaravelWallet\Contracts\BalanceState
 * @property int $id
 * @property int $balance_id
 * @property int|null $transaction_id
 * @property \O21\LaravelWallet\Numeric $value
 * @property \Carbon\Carbon $created_at
 * @property-read \O21\LaravelWallet\Contracts\Balance $balance
 * @property-read \O21\LaravelWallet\Contracts\Transaction|null $tx
 */
interface BalanceState
{
    public function balance(): BelongsTo;
    public function tx(): BelongsTo;
}
