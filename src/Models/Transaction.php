<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Models\Concerns\HasMetaColumn;
use O21\LaravelWallet\Contracts\Balance;
use O21\LaravelWallet\Contracts\Transaction as TransactionContract;
use O21\LaravelWallet\Contracts\SupportsBalance;
use O21\LaravelWallet\Contracts\TransactionProcessor;
use Illuminate\Database\Eloquent\Builder;

/**
 * O21\LaravelWallet\Models\Transaction
 *
 * @property int $id
 * @property int $user_id
 * @property string $total Sum of amount + commission
 * @property string $amount
 * @property string $commission
 * @property string $currency
 * @property string $status
 * @property string $handler_id
 * @property array|null $meta
 * @property bool $archived
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static Builder|Transaction canceled()
 * @method static Builder|Transaction expired()
 * @method static Builder|Transaction failed()
 * @method static Builder|Transaction forUser(\O21\LaravelWallet\Contracts\SupportsBalance $user)
 * @method static Builder|Transaction holding()
 * @method static Builder|Transaction newModelQuery()
 * @method static Builder|Transaction newQuery()
 * @method static Builder|Transaction newest()
 * @method static Builder|Transaction pending()
 * @method static Builder|Transaction query()
 * @method static Builder|Transaction refunded()
 * @method static Builder|Transaction success()
 * @method static Builder|Transaction whereAmount($value)
 * @method static Builder|Transaction whereArchived($value)
 * @method static Builder|Transaction whereCommission($value)
 * @method static Builder|Transaction whereCreatedAt($value)
 * @method static Builder|Transaction whereCurrency($value)
 * @method static Builder|Transaction whereHandlerId($value)
 * @method static Builder|Transaction whereId($value)
 * @method static Builder|Transaction whereMeta($value)
 * @method static Builder|Transaction whereStatus($value)
 * @method static Builder|Transaction whereTotal($value)
 * @method static Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 */
class Transaction extends Model implements TransactionContract
{
    use HasMetaColumn;

    public const UPDATED_AT = null;

    protected $casts = [
        'total'      => TrimZero::class,
        'amount'     => TrimZero::class,
        'commission' => TrimZero::class,
        'meta'       => 'array',
        'archived'   => 'boolean'
    ];

    protected $attributes = [
        'status' => TransactionStatus::PENDING
    ];

    public function toApi(): array
    {
        $result = $this->only(
            'id',
            'total',
            'amount',
            'commission',
            'currency',
            'status',
            'processor_id',
            'created_at',
            'archived'
        );

        $result['meta'] = $this->processor->prepareMeta($this->meta);

        return collect($result)
            ->mapWithKeys(
                fn($value, $key) => [(string)Str::of($key)->camel() => $value]
            )->all();
    }

    public function hasStatus(TransactionStatus|string $status): bool
    {
        return $this->status === $status;
    }

    public function updateStatus(TransactionStatus|string $status): bool
    {
        return $this->update(compact('status'));
    }

    public function processor(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->resolveProcessor(),
        )->shouldCache();
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
            'transaction' => $this
        ]);
        if (! ($processor instanceof TransactionProcessor)) {
            throw new \RuntimeException(
                "Processor {$this->processor_id} must be instance of ".TransactionProcessor::class
            );
        }

        return $processor;
    }

    public function getRelatedBalance(): ?Balance
    {
        return $this->User?->getBalance($this->currency);
    }

    public function getTotal(): string
    {
        return num($this->amount)->add($this->commission)->get();
    }

    public function User(): BelongsTo
    {
        return $this->belongsTo(config('wallet.models.user'));
    }

    public function scopePending(Builder $query): void
    {
        $query->where(
            'status',
            '=',
            TransactionStatus::PENDING
        );
    }

    public function scopeSuccess(Builder $query): void
    {
        $query->where(
            'status',
            '=',
            TransactionStatus::SUCCESS
        );
    }

    public function scopeHolding(Builder $query): void
    {
        $query->where(
            'status',
            '=',
            TransactionStatus::ON_HOLD
        );
    }

    public function scopeCanceled(Builder $query): void
    {
        $query->where(
            'status',
            '=',
            TransactionStatus::CANCELED
        );
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where(
            'status',
            '=',
            TransactionStatus::FAILED
        );
    }

    public function scopeRefunded(Builder $query): void
    {
        $query->where(
            'status',
            '=',
            TransactionStatus::REFUNDED
        );
    }

    public function scopeExpired(Builder $query): void
    {
        $query->where(
            'status',
            '=',
            TransactionStatus::EXPIRED
        );
    }

    public function scopeForUser(Builder $query, SupportsBalance $user): void
    {
        $query->where('user_id', '=', $user->getAuthIdentifier());
    }

    public function scopeNewest(Builder $query): void
    {
        $query->orderBy('created_at', 'desc');
    }
}
