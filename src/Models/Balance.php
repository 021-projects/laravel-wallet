<?php

namespace O21\LaravelWallet\Models;

use Database\Factories\BalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use O21\LaravelWallet\Casts\TrimZero;
use O21\LaravelWallet\Contracts\BalanceContract;
use O21\LaravelWallet\Contracts\TransactionContract;

/**
 * O21\LaravelWallet\Models\Balance
 *
 * @property int $id
 * @property int $user_id
 * @property string $value
 * @property string $currency
 * @property-read \App\Models\User|null $User
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
    use HasFactory;

    public function recalculate(): bool
    {
        $transactionClass = app(TransactionContract::class);

        $total = $transactionClass::whereUserId($this->user_id)
            ->whereCurrency($this->currency)
            ->accounted()
            ->sum('total');

        return $this->update(['value' => $total]);
    }

    //-----------------------------------------------------
    // MODEL DATA
    //-----------------------------------------------------

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'value', 'currency'
    ];

    protected $casts = [
        'value' => TrimZero::class
    ];

    protected $attributes = [
        'value' => 0
    ];

    //-----------------------------------------------------
    // RELATIONS
    //-----------------------------------------------------

    public function User(): BelongsTo
    {
        return $this->belongsTo(config('wallet.models.user'));
    }


    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return BalanceFactory::new();
    }
}
