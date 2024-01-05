<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
    public function payable(): MorphTo;
    public function tx(): BelongsTo;
}
