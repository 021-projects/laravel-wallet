<?php

namespace Tests\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\BalanceContract;
use O21\LaravelWallet\Contracts\UserContract;

class User extends Model implements UserContract
{
    use HasFactory;

    protected $fillable = ['name'];

    public $timestamps = false;

    protected $table = 'users';

    protected array $balances = [];

    public function getBalance(?string $currency = null): BalanceContract
    {
        if (! $currency) {
            $currency = config('wallet.currencies.basic');
        }

        if (! isset($this->balances[$currency])) {
            $attributes = [
                'user_id'  => $this->id,
                'currency' => $currency
            ];

            $balanceClass = app(BalanceContract::class);
            $this->setBalanceCached($balanceClass::firstOrCreate($attributes));
        }

        return $this->balances[$currency];
    }

    public function setBalanceCached(BalanceContract $balance): void
    {
        $this->balances[$balance->currency] = $balance;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
