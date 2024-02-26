<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\BalanceState as BalanceStateContract;

use function O21\LaravelWallet\ConfigHelpers\get_model_class;
use function O21\LaravelWallet\ConfigHelpers\table_name;

/**
 * O21\LaravelWallet\Models\BalanceState
 *
 * @property int $id
 * @property string $payable_type
 * @property int $payable_id
 * @property string $currency
 * @property int|null $transaction_id
 * @property string $value
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Database\Eloquent\Model|\O21\LaravelWallet\Contracts\Payable $payable
 * @property-read \O21\LaravelWallet\Models\Transaction|\O21\LaravelWallet\Contracts\Transaction|null $tx
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState query()
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState wherePayableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState wherePayableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BalanceState whereValue($value)
 */
class BalanceState extends Model implements BalanceStateContract
{
    public const UPDATED_AT = null;

    protected $casts = [
        'value' => TrimZero::class,
    ];

    protected $attributes = [
        'value' => 0,
    ];

    protected $fillable = [
        'transaction_id',
        'value',
        'currency',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(table_name('balance_states'));
    }

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => num($value),
            set: fn ($value) => num($value)->get()
        )->withoutObjectCaching();
    }

    public function balance(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->payable?->balance($this->currency),
        );
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tx(): BelongsTo
    {
        return $this->belongsTo(get_model_class('transaction'), 'transaction_id');
    }
}
