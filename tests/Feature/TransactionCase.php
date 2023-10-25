<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
}
