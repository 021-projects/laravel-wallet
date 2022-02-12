<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use O21\LaravelWallet\Models\Transaction;
use O21\LaravelWallet\TransactionHandlers\ReplenishmentHandler;
use O21\LaravelWallet\TransactionHandlers\WriteOffHandler;
use Tests\Models\User;
use Tests\TestCase;

class BalanceCase extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @return void
     */
    public function test_balance_creation()
    {
        $this->refreshDatabase();

        /** @var \O21\LaravelWallet\Contracts\UserContract $user */
        $user = User::factory()->create();

        $balance = $user->getBalance($this->faker->currencyCode());

        $this->assertInstanceOf(Model::class, $balance);
        $this->assertTrue($balance->exists);
    }

    /**
     * @return void
     */
    public function test_balance_replenishment()
    {
        $this->refreshDatabase();

        $this->assertFakeTransactionBalanceValue(wallet_handler_id(ReplenishmentHandler::class));
    }

    /**
     * @return void
     */
    public function test_balance_write_off()
    {
        $this->refreshDatabase();

        $this->assertFakeTransactionBalanceValue(wallet_handler_id(WriteOffHandler::class));
    }

    protected function assertFakeTransactionBalanceValue(string $handler): void
    {
        $this->refreshDatabase();

        /** @var \O21\LaravelWallet\Contracts\UserContract $user */
        $user = User::factory()->create();

        $currency = $this->faker->currencyCode();

        /** @var Model|\O21\LaravelWallet\Contracts\BalanceContract $balance */
        $balance = $user->getBalance($currency);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'handler' => $handler,
            'currency' => $currency
        ]);

        $this->assertEquals($transaction->total, $balance->fresh()->value);
    }
}
