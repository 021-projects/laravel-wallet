<?php

namespace O21\LaravelWallet\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use O21\LaravelWallet\Contracts\CurrencyConverterContract;
use O21\LaravelWallet\Models\Concerns\HasDataColumn;
use O21\LaravelWallet\Contracts\BalanceContract;
use O21\LaravelWallet\Contracts\TransactionContract;
use O21\LaravelWallet\Contracts\UserContract;
use O21\LaravelWallet\Contracts\TransactionHandlerContract;
use O21\LaravelWallet\TransactionHandlers\ReplenishmentHandler;
use O21\LaravelWallet\TransactionHandlers\WriteOffHandler;

/**
 * O21\LaravelWallet\Models\Transaction
 *
 * @property int $id
 * @property int $user_id
 * @property string $currency
 * @property string $status
 * @property string $handler
 * @property float $total
 * @property float $amount
 * @property float $commission
 * @property mixed|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereHandler($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 * @property-read \O21\LaravelWallet\Contracts\UserContract|null $User
 * @property-read string $day
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction accounted()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction completed()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction defaultOrder()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction forUser(\O21\LaravelWallet\Contracts\UserContract $user)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction processing()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction replenishment()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction writeOff()
 */
class Transaction extends Model implements TransactionContract
{
    use HasFactory;
    use HasDataColumn;

    public function toWalletTransaction(): array
    {
        $result = $this->only(
            'id',
            'status',
            'amount',
            'currency',
            'commission',
            'day',
            'created_at',
            'handler'
        );

        $result['handler_data'] = $this->handler()->getData();

        return $result;
    }

    //-----------------------------------------------------
    // IS FUNCTIONS
    //-----------------------------------------------------

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isFrozen(): bool
    {
        return $this->status === self::STATUS_FROZEN;
    }

    public function isIncome(): bool
    {
        return $this->isCompleted() && $this->total > 0;
    }

    //-----------------------------------------------------
    // FUNCTIONS
    //-----------------------------------------------------

    public static function create(
        $handler,
        UserContract $user,
        float $amount,
        string $currency,
        float $commission = 0,
        array $data = []
    ): TransactionContract {
        if (class_exists($handler)) {
            $handler = wallet_handler_id($handler);
        }

        if (! $handler) {
            throw new \Exception('Error: unknown wallet handler.');
        }

        $transactionClass = app(TransactionContract::class);

        $transaction = new $transactionClass(
            compact('handler', 'amount', 'currency', 'commission', 'data')
        );
        $transaction->User()->associate($user);
        $transaction->prepare();
        $transaction->save();

        return $transaction;
    }

    public function makeRejected(): bool
    {
        return $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function prepare(): void
    {
        $validCurrencies = [
            config('wallet.currencies.main'),
            ...config('wallet.currencies.dont_convert')
        ];

        if (! in_array($this->currency, $validCurrencies, true)) {
            $this->convertToBasicCurrency();
        }

        $this->amount = $this->handler()->validAmount();
    }

    public function convertToBasicCurrency(): void
    {
        $basicCurrency = config('wallet.currencies.main');

        /** @var \O21\LaravelWallet\Contracts\CurrencyConverterContract $converter */
        $converter = app(CurrencyConverterContract::class);

        $this->amount = $converter->convert(
            $this->attributes['amount'],
            $this->currency,
            $basicCurrency,
            $this->data
        );
        $this->commission = $converter->convert(
            $this->attributes['commission'],
            $this->currency,
            $basicCurrency,
            $this->data
        );
        $this->currency = $basicCurrency;
    }

    public function handler(): TransactionHandlerContract
    {
        if (! $this->_handler) {
            return $this->_handler = $this->resolveHandler();
        }

        return $this->_handler;
    }

    private function resolveHandler(): TransactionHandlerContract
    {
        $handler = wallet_handler($this->handler);
        if (! ($handler instanceof TransactionHandlerContract)) {
            throw new \Exception('Error: unknown transaction handler');
        }

        $handler->setTransaction($this);

        return $handler;
    }

    //-----------------------------------------------------
    // GETTERS
    //-----------------------------------------------------

    public function getUserBalance(): BalanceContract
    {
        return optional($this->User)
            ->getBalance($this->currency);
    }

    public function getDayAttribute(): ?string
    {
        return $this->created_at->copy()
            ->setHours(0)
            ->setMinutes(0)
            ->setSeconds(0)
            ->toISOString(true);
    }

    //-----------------------------------------------------
    // MODEL DATA
    //-----------------------------------------------------

    public const UPDATED_AT = null;

    protected $casts = [
        'total' => 'float',
        'amount' => 'float',
        'commission' => 'float',
        'data' => 'array'
    ];

    protected $attributes = [
        'status' => 'completed'
    ];

    protected $_handler;

    protected static $unguarded = true;

    //-----------------------------------------------------
    // RELATIONS
    //-----------------------------------------------------

    public function User(): BelongsTo
    {
        return $this->belongsTo(config('wallet.models.user'));
    }

    //-----------------------------------------------------
    // SCOPES
    //-----------------------------------------------------

    /**
     * @param \Illuminate\Database\Query\Builder|self $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessing($query)
    {
        return $query->whereStatus(self::STATUS_PROCESSING);
    }

    /**
     * @param \Illuminate\Database\Query\Builder|self $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->whereStatus(self::STATUS_COMPLETED);
    }

    /**
     * Find accounted transactions.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeAccounted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_FROZEN
        ]);
    }

    /**
     * Find replenishments.
     *
     * @param \Illuminate\Database\Query\Builder|self $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReplenishment($query)
    {
        return $query->whereHandler(wallet_handler_id(ReplenishmentHandler::class));
    }

    /**
     * Find write off transactions.
     *
     * @param \Illuminate\Database\Query\Builder|self $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWriteOff($query)
    {
        return $query->whereHandler(wallet_handler_id(WriteOffHandler::class));
    }

    /**
     * Find transactions for user.
     *
     * @param \Illuminate\Database\Query\Builder|self $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, UserContract $user)
    {
        return $query->defaultOrder()
            ->whereUserId($user->id);
    }

    public function scopeDefaultOrder($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TransactionFactory::new();
    }
}
