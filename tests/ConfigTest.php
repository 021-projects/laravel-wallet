<?php

namespace O21\LaravelWallet\Tests;

use O21\LaravelWallet\Models\Balance;
use O21\LaravelWallet\Models\BalanceState;
use O21\LaravelWallet\Models\ShadowBalance;
use O21\LaravelWallet\Models\Transaction;
use Workbench\App\Models\User;

class ConfigTest extends TestCase
{
    public function test_default_currency(): void
    {
        config(['wallet.default_currency' => 'USD']);

        $this->assertEquals('USD', config('wallet.default_currency'));

        $user = User::factory()->create();

        $this->assertEquals('USD', $user->balance()->currency);
    }

    public function test_table_names(): void
    {
        config([
            'wallet.table_names.balances' => 'mock_balances',
            'wallet.table_names.balance_states' => 'mock_balance_states',
            'wallet.table_names.transactions' => 'mock_transactions',
            'wallet.table_names.shadow_balances' => 'mock_shadow_balances',
        ]);

        $this->assertEquals('mock_balances', Balance::make()->getTable());
        $this->assertEquals('mock_balance_states', BalanceState::make()->getTable());
        $this->assertEquals('mock_transactions', Transaction::make()->getTable());
        $this->assertEquals('mock_shadow_balances', ShadowBalance::make()->getTable());
    }

    public function test_processors(): void
    {
        config([
            'wallet.processors.deposit' => MockProcessor::class,
        ]);

        $tx = deposit(100, 'USD')->to(User::factory()->create())->overcharge()->commit();

        $this->assertInstanceOf(MockProcessor::class, $tx->processor);
    }

    public function test_transactions_route_key(): void
    {
        $key = 'uuid';
        config(['wallet.transactions.route_key' => $key]);

        $user = User::factory()->create();
        $tx = deposit(100, 'USD')->to($user)->overcharge()->commit();

        $this->assertEquals($key, $tx->getRouteKeyName());
        $this->assertIsString($tx->getRouteKey());
        $this->assertArrayHasKey($key, $tx->toApi());

        $key = 'id';
        config(['wallet.transactions.route_key' => $key]);
        $tx = deposit(100, 'USD')->to($user)->overcharge()->commit();

        $this->assertEquals($key, $tx->getRouteKeyName());
        $this->assertIsInt($tx->getRouteKey());
        $this->assertArrayHasKey($key, $tx->toApi());
    }

    public function test_transactions_route_key_not_set(): void
    {
        config()->offsetUnset('wallet.transactions.route_key');

        $user = User::factory()->create();

        $tx = deposit(100, 'USD')->to($user)->overcharge()->commit();

        $this->assertEquals('uuid', $tx->getRouteKeyName());
    }

    public function test_transactions_route_key_is_null(): void
    {
        config(['wallet.transactions.route_key' => null]);

        $user = User::factory()->create();

        $tx = deposit(100, 'USD')->to($user)->overcharge()->commit();

        $this->assertEquals('uuid', $tx->getRouteKeyName());
    }
}

class MockProcessor extends \O21\LaravelWallet\Transaction\Processors\DepositProcessor
{
}
