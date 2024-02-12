<?php

namespace O21\LaravelWallet\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use O21\LaravelWallet\Contracts\BalanceState as BalanceStateContract;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Models\BalanceState;
use O21\LaravelWallet\Tests\Feature\Concerns\BalanceTest;
use O21\LaravelWallet\Tests\Models\User;
use O21\LaravelWallet\Tests\TestCase;

class BalanceStateCase extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    use BalanceTest;

    public function test_logging(): void
    {
        config([
            'wallet.balance.log_states' => true
        ]);

        [$user, $currency, $balance] = $this->createBalance();
        [$user2,] = $this->createBalance();

        $transfers = [];
        $transferSum = 0;

        for ($i = 0; $i < 10; $i++) {
            $tx = $this->createTransfer($user, $user2, 100, $currency);
            $transferSum += 100;
            $transfers[] = compact('tx', 'transferSum');
        }

        $expectedStatesCount = 20; // 10 transfers * 2 states

        foreach ($transfers as $transfer) {
            /** @var Transaction $tx */
            $tx = $transfer['tx'];
            $transferSum = $transfer['transferSum'];

            $this->assertNotNull($tx->fromState);
            $this->assertNotNull($tx->toState);
            $this->assertNotNull($tx->fromState->tx);
            $this->assertNotNull($tx->toState->tx);
            $this->assertInstanceOf(BalanceStateContract::class, $tx->fromState);
            $this->assertInstanceOf(BalanceStateContract::class, $tx->toState);
            $this->assertTrue($tx->fromState->balance->is($balance));
            $this->assertTrue($tx->toState->balance->is($user2->balance($currency)));
            $this->assertTrue($tx->fromState->tx->is($tx));
            $this->assertTrue($tx->toState->tx->is($tx));

            $this->assertEquals((string)-$transferSum, (string)$tx->fromState->value);
            $this->assertEquals((string)$transferSum, (string)$tx->toState->value);
        }

        $stateModel = app(BalanceStateContract::class);
        $this->assertEquals($expectedStatesCount, $stateModel::count());
    }

    public function test_logging_without_tx(): void
    {
        config([
            'wallet.balance.log_states' => false
        ]);

        [$user, $currency, $balance] = $this->createBalance();
        [$user2] = $this->createBalance();

        $tx = $this->createTransfer($user, $user2, 100, $currency);

        $state = $balance->logState();

        // tx is last transfer
        $this->assertNull($state);

        $stateModel = app(BalanceStateContract::class);
        $this->assertEquals(1, $stateModel::count());
    }

    public function test_log_recreate_on_tx_status_changed(): void
    {
        config([
            'wallet.balance.log_states' => true
        ]);

        [$user, $currency, $balance] = $this->createBalance();
        [$user2] = $this->createBalance();

        $tx = $this->createTransfer($user, $user2, 100, $currency);

        $this->assertNotNull($tx);
        $this->assertNotNull($tx->fromState);
        $this->assertNotNull($tx->toState);
        $this->assertEquals('-100', (string)$tx->fromState->value);
        $this->assertEquals('100', (string)$tx->toState->value);
        $this->assertTrue($tx->hasStatus(TransactionStatus::SUCCESS));

        $tx->updateStatus(TransactionStatus::FAILED);
        $tx = $tx->fresh();

        $this->assertTrue($tx->hasStatus(TransactionStatus::FAILED));
        $this->assertNotNull($tx->fromState);
        $this->assertNotNull($tx->toState);
        $this->assertEquals('0', (string)$tx->fromState->value);
        $this->assertEquals('0', (string)$tx->toState->value);
    }

    public function test_logging_disabled(): void
    {
        config([
            'wallet.balance.log_states' => false
        ]);

        [$user, $currency] = $this->createBalance();
        [$user2] = $this->createBalance();

        $tx = $this->createTransfer($user, $user2, 100, $currency);

        // tx is last transfer
        $this->assertNull($tx->fromState);
        $this->assertNull($tx->toState);

        $stateModel = app(BalanceStateContract::class);
        $this->assertEquals(0, $stateModel::count());
    }

    public function test_model_not_specified_in_config(): void
    {
        config([
            'wallet.balance.log_states'  => true,
        ]);

        config()->offsetUnset('wallet.models.balance_state');

        [$user, $currency] = $this->createBalance();
        [$user2] = $this->createBalance();

        [$user, $currency] = $this->createBalance();
        [$user2] = $this->createBalance();

        $tx = $this->createTransfer($user, $user2, 100, $currency);

        $this->assertInstanceOf(BalanceState::class, $tx->fromState);
        $this->assertInstanceOf(BalanceState::class, $tx->toState);

        $stateModel = app(BalanceStateContract::class);
        $this->assertEquals(2, $stateModel::count());
    }

    public function test_rebuild_states_command(): void
    {
        config([
            'wallet.balance.log_states' => false
        ]);

        [$user, $currency] = $this->createBalance();
        [$user2] = $this->createBalance();

        $transfers = [];
        $transferSum = 0;

        for ($i = 0; $i < 10; $i++) {
            $tx = $this->createTransfer($user, $user2, 100, $currency);
            $transferSum += 100;
            $transfers[] = compact('tx', 'transferSum');
        }

        $stateModel = app(BalanceStateContract::class);
        $expectedStatesCount = 20; // 10 transfers * 2 states

        $this->assertEquals(0, $stateModel::count());

        $this->artisan('wallet:rebuild-tx-balance-states');

        foreach ($transfers as $transfer) {
            /** @var Transaction $tx */
            $tx = $transfer['tx'];
            $transferSum = $transfer['transferSum'];

            $this->assertNotNull($tx->fromState);
            $this->assertNotNull($tx->toState);
            $this->assertInstanceOf(BalanceStateContract::class, $tx->fromState);
            $this->assertInstanceOf(BalanceStateContract::class, $tx->toState);

            $this->assertEquals((string)-$transferSum, (string)$tx->fromState->value);
            $this->assertEquals((string)$transferSum, (string)$tx->toState->value);
        }

        $this->assertEquals($expectedStatesCount, $stateModel::count());
    }

    public function test_state_not_deleted_when_balance_deleted(): void
    {
        config([
            'wallet.balance.log_states' => true
        ]);

        [$user, $currency] = $this->createBalance();
        [$user2] = $this->createBalance();

        $tx = $this->createTransfer($user, $user2, 100, $currency);

        $this->assertNotNull($tx->fromState);
        $this->assertNotNull($tx->toState);

        $tx->fromState->balance->delete();

        $tx = $tx->fresh();

        $this->assertNotNull($tx->fromState);
        $this->assertNotNull($tx->toState);
    }

    public function test_states_deleted_when_tx_deleted(): void
    {
        config([
            'wallet.balance.log_states' => true
        ]);

        [$user, $currency] = $this->createBalance();
        [$user2] = $this->createBalance();

        $tx = $this->createTransfer($user, $user2, 100, $currency);

        $this->assertNotNull($tx->fromState);
        $this->assertNotNull($tx->toState);

        $fromState = $tx->fromState;
        $toState = $tx->toState;

        $tx->delete();

        $this->assertNull($fromState->fresh());
        $this->assertNull($toState->fresh());
    }

    protected function createTransfer(User $from, User $to, $amount, $currency): Transaction
    {
        return transfer($amount, $currency)
            ->from($from)
            ->to($to)
            ->overcharge()
            ->commit();
    }
}
