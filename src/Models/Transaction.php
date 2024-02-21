<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\Balance;
use O21\LaravelWallet\Contracts\Payable;
use O21\LaravelWallet\Contracts\Transaction as TransactionContract;
use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Models\Concerns\HasMetaColumn;

/**
 * O21\LaravelWallet\Models\Transaction
 *
 * @property int $id
 * @property string $payable_type
 * @property int $payable_id
 * @property string $total Sum of amount + commission
 * @property string $amount
 * @property string $commission
 * @property string $currency
 * @property string $status
 * @property string $processor_id
 * @property array|null $meta
 * @property bool $archived
 * @property \Illuminate\Support\Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newest()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePayableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePayableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTotal($value)
 *
 * @property-read \Illuminate\Database\Eloquent\Model|Payable $from
 * @property-read \Illuminate\Database\Eloquent\Model|Payable $to
 * @property-read \Illuminate\Database\Eloquent\Model|\O21\LaravelWallet\Contracts\BalanceState|null $fromState
 * @property-read \Illuminate\Database\Eloquent\Model|\O21\LaravelWallet\Contracts\BalanceState|null $toState
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction from(\O21\LaravelWallet\Contracts\Payable $from)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction to(\O21\LaravelWallet\Contracts\Payable $to)
 *
 * @property string|null $from_type
 * @property int|null $from_id
 * @property string|null $to_type
 * @property int|null $to_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction accountable(bool $accountable)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereFromType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereToType($value)
 *
 * @property string $received received = amount - commission
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereReceived($value)
 */
class Transaction extends Model implements TransactionContract
{
    use HasMetaColumn;

    public const UPDATED_AT = null;

    protected $casts = [
        'received' => TrimZero::class,
        'amount' => TrimZero::class,
        'commission' => TrimZero::class,
        'meta' => 'array',
        'archived' => 'boolean',
    ];

    protected $attributes = [
        'status' => TransactionStatus::PENDING,
        'received' => '0',
        'amount' => '0',
        'commission' => '0',
        'archived' => false,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('wallet.table_names.transactions', 'transactions'));
    }

    public function toApi(): array
    {
        $result = $this->only(
            'id',
            'received',
            'amount',
            'commission',
            'currency',
            'status',
            'processor_id',
            'created_at',
            'archived'
        );

        $result['meta'] = $this->processor->prepareMeta($this->meta ?? []);

        return collect($result)
            ->mapWithKeys(
                fn ($value, $key) => [(string) Str::of($key)->camel() => $value]
            )->all();
    }

    public function recalculateBalances(): void
    {
        if ($this->wasChanged('currency')) {
            $oldCurrency = $this->getOriginal('currency');
            $this->from?->balance($oldCurrency)?->recalculate();
            $this->to?->balance($oldCurrency)?->recalculate();
        }

        $this->from?->balance($this->currency)?->recalculate();
        $this->to?->balance($this->currency)?->recalculate();
    }

    public function logStates(): void
    {
        $balanceClass = app(Balance::class);
        if (! method_exists($balanceClass, 'logState')) {
            return;
        }
        $this->from?->balance($this->currency)?->logState($this);
        $this->to?->balance($this->currency)?->logState($this);
    }

    public function deleteStates(): void
    {
        $this->fromState?->delete();
        $this->toState?->delete();
    }

    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function updateStatus(string $status): bool
    {
        $this->status = $status;

        return $this->save();
    }

    public function processor(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveProcessor(),
        )->shouldCache();
    }

    public function setProcessor(string $id): void
    {
        $this->processor_id = $id;
        unset($this->attributeCastCache['processor']);
    }

    private function resolveProcessor(): TransactionProcessor
    {
        $processorClass = config("wallet.processors.{$this->processor_id}");
        if (! $processorClass || ! class_exists($processorClass)) {
            throw new \RuntimeException(
                "Processor {$this->processor_id} not found"
            );
        }

        $processor = app($processorClass, [
            'transaction' => $this,
        ]);
        if (! ($processor instanceof TransactionProcessor)) {
            throw new \RuntimeException(
                "Processor {$this->processor_id} must be instance of ".TransactionProcessor::class
            );
        }

        return $processor;
    }

    public function from(): MorphTo
    {
        return $this->morphTo();
    }

    public function to(): MorphTo
    {
        return $this->morphTo();
    }

    public function fromState(): HasOne
    {
        return $this->hasOne(config('wallet.models.balance_state') ?? BalanceState::class)
            ->where([
                ['payable_id', '=', $this->from_id],
                ['payable_type', '=', $this->from_type],
            ]);
    }

    public function toState(): HasOne
    {
        return $this->hasOne(config('wallet.models.balance_state') ?? BalanceState::class)
            ->where([
                ['payable_id', '=', $this->to_id],
                ['payable_type', '=', $this->to_type],
            ]);
    }

    public function scopeFrom(Builder $query, Payable $from): void
    {
        $query->where('from_type', '=', $from->getMorphClass())
            ->where('from_id', '=', $from->getKey());
    }

    public function scopeTo(Builder $query, Payable $to): void
    {
        $query->where('to_type', '=', $to->getMorphClass())
            ->where('to_id', '=', $to->getKey());
    }

    public function scopeAccountable(Builder $query, bool $accountable = true): void
    {
        if ($accountable) {
            $query->whereIn('status', TransactionStatus::accounting());
        } else {
            $query->whereNotIn('status', TransactionStatus::accounting());
        }
    }
}
