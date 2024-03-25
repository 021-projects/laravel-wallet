<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\Payable;
use O21\LaravelWallet\Contracts\Transaction as TransactionContract;
use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Exception\InvalidTxProcessorException;
use O21\LaravelWallet\Exception\UnknownTxProcessorException;
use O21\LaravelWallet\Models\Concerns\HasMetaColumn;

use function O21\LaravelWallet\ConfigHelpers\get_model_class;
use function O21\LaravelWallet\ConfigHelpers\get_tx_processor_class;
use function O21\LaravelWallet\ConfigHelpers\table_name;
use function O21\LaravelWallet\ConfigHelpers\tx_currency_scaling;
use function O21\LaravelWallet\ConfigHelpers\tx_route_key;

/**
 * O21\LaravelWallet\Models\Transaction
 *
 * @property int $id
 * @property string $uuid
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
 * @property bool $invisible
 * @property int $batch
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
 * @property-read \Illuminate\Database\Eloquent\Model|Payable|null $from
 * @property-read \Illuminate\Database\Eloquent\Model|Payable|null $to
 * @property-read \Illuminate\Database\Eloquent\Model|\O21\LaravelWallet\Contracts\BalanceState|null $fromState
 * @property-read \Illuminate\Database\Eloquent\Model|\O21\LaravelWallet\Contracts\BalanceState|null $toState
 * @property-read \Illuminate\Database\Eloquent\Collection|\O21\LaravelWallet\Models\Transaction[] $neighbours
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction from(\O21\LaravelWallet\Contracts\Payable $from)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction to(\O21\LaravelWallet\Contracts\Payable $to)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction relatedTo(\O21\LaravelWallet\Contracts\Payable $payable)
 *
 * @property string|null $from_type
 * @property int|null $from_id
 * @property string|null $to_type
 * @property int|null $to_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction accountable(bool $accountable = true)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereFromType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereToType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereBatch($value)
 *
 * @property string $received received = amount - commission
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereReceived($value)
 */
class Transaction extends Model implements TransactionContract
{
    use HasMetaColumn, HasUuids;

    public const UPDATED_AT = null;

    protected $casts = [
        'received' => TrimZero::class,
        'amount' => TrimZero::class,
        'commission' => TrimZero::class,
        'meta' => 'array',
        'archived' => 'boolean',
        'invisible' => 'boolean',
    ];

    protected $attributes = [
        'status' => TransactionStatus::PENDING,
        'received' => '0',
        'amount' => '0',
        'commission' => '0',
        'archived' => false,
        'invisible' => false,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(table_name('transactions'));
    }

    public function toApi(...$opts): array
    {
        $parties = data_get($opts, 'parties', true);
        $neighbours = data_get($opts, 'neighbours', false);
        $meta = data_get($opts, 'meta', true);
        $camelKeys = data_get($opts, 'camelKeys', true);

        $result = $this->only(
            $this->getRouteKeyName(),
            'amount',
            'received',
            'commission',
            'currency',
            'status',
            'processor_id',
            'archived',
            'batch',
            'created_at',
        );

        if ($parties) {
            $from = $this->from;
            $to = $this->to;
            $result['from'] = null;
            $result['to'] = null;
            if ($from) {
                $result['from'] = method_exists($from, 'toApi') ? $from->toApi() : $from->toArray();
            }
            if ($to) {
                $result['to'] = method_exists($to, 'toApi') ? $to->toApi() : $to->toArray();
            }
        }

        if ($neighbours) {
            $result['neighbours'] = $this->neighbours->map->toApi(
                parties: $parties,
                neighbours: false,
                meta: $meta
            );
        }

        if ($meta) {
            $result['meta'] = $this->processor->prepareMeta($this->meta ?? []);
        }

        if ($camelKeys) {
            $result = collect($result)
                ->mapWithKeys(
                    fn ($value, $key) => [(string) Str::of($key)->camel() => $value]
                )->all();
        }

        return $result;
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

    public function normalizeNumbers(): void
    {
        $scale = tx_currency_scaling($this->currency);
        $this->amount = num($this->amount)->scale($scale)->get();
        $this->commission = num($this->commission)->scale($scale)->get();
        $this->received = num($this->amount)->sub($this->commission)->scale($scale)->get();
    }

    public function logStates(): void
    {
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
        $processorClass = get_tx_processor_class($this->processor_id);

        throw_if(
            ! class_exists($processorClass),
            UnknownTxProcessorException::class,
            $this->processor_id
        );

        $processor = app($processorClass, [
            'transaction' => $this,
            'tx' => $this,
        ]);

        throw_if(
            ! ($processor instanceof TransactionProcessor),
            InvalidTxProcessorException::class,
            $this->processor_id
        );

        return $processor;
    }

    public function nextBatch(): int
    {
        return static::query()->max('batch') + 1;
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
        return $this->hasOne(get_model_class('balance_state'))
            ->where([
                ['payable_id', '=', $this->from_id],
                ['payable_type', '=', $this->from_type],
            ]);
    }

    public function toState(): HasOne
    {
        return $this->hasOne(get_model_class('balance_state'))
            ->where([
                ['payable_id', '=', $this->to_id],
                ['payable_type', '=', $this->to_type],
            ]);
    }

    public function neighbours(): HasMany
    {
        return $this->hasMany(
            get_model_class('transaction'),
            'batch',
            'batch'
        )->where('id', '!=', $this->id);
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

    public function scopeRelatedTo(Builder $query, Payable $payable): void
    {
        $query->where(fn ($q) => $q->from($payable)
            ->orWhere(fn ($q) => $q->to($payable)));
    }

    public function scopeAccountable(Builder $query, bool $accountable = true): void
    {
        if ($accountable) {
            $query->whereIn('status', TransactionStatus::accounting());
        } else {
            $query->whereNotIn('status', TransactionStatus::accounting());
        }
    }

    public function scopeSkipInvisible(Builder $query): void
    {
        $query->where('invisible', false);
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName()
    {
        return tx_route_key();
    }
}
