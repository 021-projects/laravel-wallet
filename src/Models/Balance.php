<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\Balance as BalanceContract;
use O21\LaravelWallet\Contracts\Transaction;

/**
 * O21\LaravelWallet\Models\Balance
 *
 * @property int $id
 * @property string $payable_type
 * @property int $payable_id
 * @property \O21\LaravelWallet\Numeric $value
 * @property string $value_pending
 * @property string $value_on_hold
 * @property string $currency
 * @property-read Model|\Eloquent $payable
 * @property-read \O21\LaravelWallet\Numeric $sent
 * @property-read \O21\LaravelWallet\Numeric $received
 * @method static Builder|Balance newModelQuery()
 * @method static Builder|Balance newQuery()
 * @method static Builder|Balance query()
 * @method static Builder|Balance whereCurrency($value)
 * @method static Builder|Balance whereId($value)
 * @method static Builder|Balance wherePayableId($value)
 * @method static Builder|Balance wherePayableType($value)
 * @method static Builder|Balance whereValue($value)
 * @method static Builder|Balance whereValueOnHold($value)
 * @method static Builder|Balance whereValuePending($value)
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
        'payable_type',
        'payable_id',
        'currency',
        'value',
        'value_pending',
        'value_on_hold',
    ];

    public function recalculate(): bool
    {
        $value = $this->received->sub($this->sent)->get();

        $attributes = compact('value');

        $extraValues = config('wallet.balance.extra_values', []);

        foreach ($extraValues as $status => $active) {
            if (! $active) {
                continue;
            }

            $attributeKey = "value_{$status}";

            $sent = num($this->transactions()->from($this->payable)->whereStatus($status)->sum('amount'));
            $received = num($this->transactions()->to($this->payable)->whereStatus($status)->sum('received'));

            $attributes[$attributeKey] = $received->sub($sent)->get();
        }

        return $this->update($attributes);
    }

    public function logState(?Transaction $tx = null): void
    {
        $value = (string)$this->value;

        if ($tx) {
            // log state before transaction
            $sent = $this->transactions()
                ->accountable()
                ->from($this->payable)
                ->where('id', '<=', $tx->id)
                ->sum('amount');
            $received = $this->transactions()
                ->accountable()
                ->to($this->payable)
                ->where('id', '<=', $tx->id)
                ->sum('received');
            $value = num($received)->sub($sent)->get();
        }

        $this->states()->create([
            'transaction_id' => $tx?->id,
            'value'          => $value,
        ]);
    }

    public function equals(float|int|string $value): bool
    {
        return $this->value->equals($value);
    }

    public function greaterThan(float|int|string $value): bool
    {
        return $this->value->greaterThan($value);
    }

    public function greaterThanOrEqual(float|int|string $value): bool
    {
        return $this->value->greaterThanOrEqual($value);
    }

    public function lessThan(float|int|string $value): bool
    {
        return $this->value->lessThan($value);
    }

    public function lessThanOrEqual(float|int|string $value): bool
    {
        return $this->value->lessThanOrEqual($value);
    }

    public function sent(): Attribute
    {
        return Attribute::make(
            get: fn($value) => num($this->transactions()->accountable()->from($this->payable)->sum('amount'))
        )->withoutObjectCaching();
    }

    public function received(): Attribute
    {
        return Attribute::make(
            get: fn($value) => num($this->transactions()->accountable()->to($this->payable)->sum('received'))
        )->withoutObjectCaching();
    }

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn($value) => num($value),
            set: fn($value) => num($value)->get()
        )->withoutObjectCaching();
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function states(): HasMany
    {
        return $this->hasMany(config('wallet.models.balance_state') ?? BalanceState::class);
    }

    public function transactions(): Builder
    {
        $transactionClass = app(Transaction::class);
        return $transactionClass::where(function (Builder $query) {
            $query->where(function (Builder $query) {
                $query->where('from_type', $this->payable_type)
                    ->where('from_id', $this->payable_id);
            })->orWhere(function (Builder $query) {
                $query->where('to_type', $this->payable_type)
                    ->where('to_id', $this->payable_id);
            });
        })->whereCurrency($this->currency);
    }
}
