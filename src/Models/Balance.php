<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\Balance as BalanceContract;
use O21\LaravelWallet\Contracts\Transaction;

/**
 * O21\LaravelWallet\Models\Balance
 *
 * @property int $id
 * @property int $user_id
 * @property string $value
 * @property string $currency
 * @property-read \O21\LaravelWallet\Contracts\SupportsBalance $User
 * @property-read \O21\LaravelWallet\Numeric $valueNum
 * @method static \Illuminate\Database\Eloquent\Builder|Balance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Balance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Balance query()
 * @method static \Illuminate\Database\Eloquent\Builder|Balance whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Balance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Balance whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Balance whereValue($value)
 * @mixin \Eloquent
 */
class Balance extends Model implements BalanceContract
{
    public $timestamps = false;

    protected $casts = [
        'value' => TrimZero::class
    ];

    protected $attributes = [
        'value' => 0
    ];

    protected $fillable = [
        'user_id',
        'currency',
        'value'
    ];

    public function recalculate(): bool
    {
        $transactionClass = app(Transaction::class);

        $total = $transactionClass::whereUserId($this->user_id)
            ->whereCurrency($this->currency)
            ->success()
            ->sum('total');

        return $this->update(['value' => $total]);
    }

    public function equals(float|int|string $value): bool
    {
        return $this->valueNum->equals($value);
    }

    public function greaterThan(float|int|string $value): bool
    {
        return $this->valueNum->greaterThan($value);
    }

    public function greaterThanOrEqual(float|int|string $value): bool
    {
        return $this->valueNum->greaterThanOrEqual($value);
    }

    public function lessThan(float|int|string $value): bool
    {
        return $this->valueNum->lessThan($value);
    }

    public function lessThanOrEqual(float|int|string $value): bool
    {
        return $this->valueNum->lessThanOrEqual($value);
    }

    public function valueNum(): Attribute
    {
        return Attribute::make(
            get: fn($value) => num($this->value)
        )->withoutObjectCaching();
    }

    public function User(): BelongsTo
    {
        return $this->belongsTo(config('wallet.models.user'));
    }
}
