<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\BalanceState as BalanceStateContract;

class BalanceState extends Model implements BalanceStateContract
{
    public const UPDATED_AT = null;

    protected $casts = [
        'value'         => TrimZero::class,
    ];

    protected $attributes = [
        'value'         => 0,
    ];

    protected $fillable = [
        'transaction_id',
        'value',
    ];

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn($value) => num($value),
            set: fn($value) => num($value)->get()
        )->withoutObjectCaching();
    }

    public function balance(): BelongsTo
    {
        return $this->belongsTo(config('wallet.models.balance'));
    }

    public function tx(): BelongsTo
    {
        return $this->belongsTo(config('wallet.models.transaction'));
    }
}
