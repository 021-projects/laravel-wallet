<?php

namespace O21\LaravelWallet\Tests;

use O21\LaravelWallet\Models\Balance;
use O21\LaravelWallet\Models\BalanceState;
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
        ]);

        $this->assertEquals('mock_balances', Balance::make()->getTable());
        $this->assertEquals('mock_balance_states', BalanceState::make()->getTable());
        $this->assertEquals('mock_transactions', Transaction::make()->getTable());
    }

    public function test_processors(): void
    {
        config([
            'wallet.processors.deposit' => MockProcessor::class,
        ]);

        $tx = deposit(100, 'USD')->to(User::factory()->create())->overcharge()->commit();

        $this->assertInstanceOf(MockProcessor::class, $tx->processor);
    }
}

class MockProcessor extends \O21\LaravelWallet\Transaction\Processors\DepositProcessor
{
}
