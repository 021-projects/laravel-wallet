<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\Balance as BalanceContract;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Enums\TransactionStatus;

/**
 * O21\LaravelWallet\Models\Balance
 *
 * @property int $id
 * @property int $user_id
 * @property string $value
 * @property string $value_pending
 * @property string $value_on_hold
 * @property string $currency
 * @property-read \O21\LaravelWallet\Contracts\SupportsBalance $User
 * @property-read \O21\LaravelWallet\Numeric $valueNum
 * @method static Builder|Balance newModelQuery()
 * @method static Builder|Balance newQuery()
 * @method static Builder|Balance query()
 * @method static Builder|Balance whereCurrency($value)
 * @method static Builder|Balance whereId($value)
 * @method static Builder|Balance whereUserId($value)
 * @method static Builder|Balance whereValue($value)
 * @mixin \Eloquent
 */
class Balance extends Model implements BalanceContract
{
    public $timestamps = false;

    protected $casts = [
        'value'         => TrimZero::class,
        'value_pending' => TrimZero::class,
        'value_on_hold' => TrimZero::class,
    ];

    protected $attributes = [
        'value'         => 0,
        'value_pending' => 0,
        'value_on_hold' => 0,
    ];

    protected $fillable = [
        'user_id',
        'currency',
        'value',
        'value_pending',
        'value_on_hold',
    ];

    public function recalculate(): bool
    {
        $transactionClass = app(Transaction::class);

        $accountingStatuses = config('wallet.balance.accounting_statuses', [
            TransactionStatus::SUCCESS,
        ]);
        $accountingStatuses = array_map(
            static fn($status) => $status instanceof TransactionStatus ? $status->value : $status,
            $accountingStatuses
        );

        $value = $transactionClass::whereUserId($this->user_id)
            ->whereCurrency($this->currency)
            ->whereIn('status', $accountingStatuses)
            ->sum('total');

        $attributes = compact('value');

        $extraValues = config('wallet.balance.extra_values', []);

        foreach ($extraValues as $status => $active) {
            if (! $active) {
                continue;
            }

            $attributeKey = "value_{$status}";
            $attributes[$attributeKey] = $transactionClass::whereUserId($this->user_id)
                ->whereCurrency($this->currency)
                ->whereStatus($status)
                ->sum('total');
        }

        return $this->update($attributes);
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
