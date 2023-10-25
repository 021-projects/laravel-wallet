<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Exception\InsufficientFundsException;
use Tests\Feature\Concerns\BalanceTest;
use Tests\TestCase;

class TransactionCase extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    use BalanceTest;

    public function test_deposit(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        deposit(100, $currency)
            ->to($user)
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            100
        );
    }

    public function test_deposit_with_commission(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        deposit(100, $currency)
            ->to($user)
            ->commission(-10)
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            90
        );
    }

    public function test_charge(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        deposit(100, $currency)
            ->to($user)
            ->commit();

        $this->assertBalanceRefreshEquals($balance, 100);

        charge(50, $currency)
            ->from($user)
            ->commit();

        $this->assertBalanceRefreshEquals($balance, 50);
    }

    public function test_charge_with_commission(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        deposit(100, $currency)
            ->to($user)
            ->commit();

        $this->assertBalanceRefreshEquals($balance, 100);

        charge(50, $currency)
            ->from($user)
            ->commission(-10)
            ->commit();

        $this->assertBalanceRefreshEquals($balance, 40);
    }

    public function test_throw_not_enough_funds(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $this->expectException(InsufficientFundsException::class);

        charge(999, $currency)
            ->from($user)
            ->commit();
    }

    public function test_overcharge_without_throw_not_enough_funds(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        charge(999, $currency)
            ->from($user)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            -999
        );
    }

    public function test_not_change_balance_if_throws_during_creation(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $this->expectException(\RuntimeException::class);

        deposit(100, $currency)
            ->to($user)
            ->before(static function () {
                throw new \RuntimeException('test');
            })
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            0
        );

        $this->expectException(\RuntimeException::class);

        charge(100, $currency)
            ->from($user)
            ->after(static function () {
                throw new \RuntimeException('test');
            })
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            0
        );
    }

    public function test_has_status(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $transaction = deposit(100, $currency)
            ->to($user)
            ->commit();

        $this->assertTrue($transaction->hasStatus(TransactionStatus::SUCCESS));
        $this->assertFalse($transaction->hasStatus(TransactionStatus::PENDING));
        $this->assertTrue($transaction->hasStatus('success'));
        $this->assertFalse($transaction->hasStatus('pending'));
    }

    public function test_update_status(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $transaction = deposit(100, $currency)
            ->to($user)
            ->commit();

        $this->assertTrue($transaction->hasStatus(TransactionStatus::SUCCESS));
        $this->assertFalse($transaction->hasStatus(TransactionStatus::PENDING));

        $transaction->updateStatus(TransactionStatus::PENDING);

        $this->assertFalse($transaction->hasStatus(TransactionStatus::SUCCESS));
        $this->assertTrue($transaction->hasStatus(TransactionStatus::PENDING));

        $this->assertBalanceRefreshEquals(
            $balance,
            0
        );
    }

    public function test_to_api(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $transaction = deposit(100, $currency)
            ->to($user)
            ->commit();

        $result = $transaction->toApi();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('processorId', $result);
        $this->assertArrayHasKey('createdAt', $result);
        $this->assertArrayHasKey('archived', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertIsArray($result['meta']);
    }

    public function test_meta(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $transaction = deposit(100, $currency)
            ->to($user)
            ->meta([
                'test' => 'value',
            ])
            ->commit();

        $this->assertNull($transaction->getMeta('unknown'));
        $this->assertEquals('value', $transaction->getMeta('test'));

        $transaction->setMeta('test', 'value2');

        $this->assertEquals('value2', $transaction->getMeta('test'));

        $transaction->updateMeta('test', 'value3');

        $this->assertEquals('value3', $transaction->getMeta('test'));

        $transaction->updateMeta([
            'test2' => 'value3',
        ]);

        $this->assertArrayHasKey('test', $transaction->getMeta());
        $this->assertEquals('value3', $transaction->getMeta('test2'));
    }
}
