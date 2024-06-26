<?php

namespace O21\LaravelWallet\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithFaker;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\Numeric\Numeric;
use O21\LaravelWallet\Tests\Concerns\BalanceSeed;

class BalanceTest extends TestCase
{
    private const SMALL_VALUE = 0.00000001;

    private const SMALL_VALUE_STR = '0.00000001';

    private const SMALLEST_VALUE = 0.0000000001;

    private const SMALLEST_VALUE_STR = '0.0000000001';

    use BalanceSeed;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the scaling for the currencies to prevent test failing with small values
        config(['wallet.currency_scaling' => []]);
    }

    public function test_creation(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $this->assertInstanceOf(Model::class, $balance);
        $this->assertTrue($balance->exists);
    }

    public function test_balance_interface(): void
    {
        /** @var \O21\LaravelWallet\Contracts\Balance $balance */
        [$user, $currency, $balance] = $this->createBalance();

        $this->assertInstanceOf(Model::class, $balance);
        $this->assertTrue($balance->exists);

        $this->assertTrue($balance->recalculate());
        $this->assertTrue($balance->equals(0));

        $balance->value = 100;

        $this->assertInstanceOf(Numeric::class, $balance->value);
        $this->assertInstanceOf(Numeric::class, $balance->value_pending);
        $this->assertInstanceOf(Numeric::class, $balance->value_on_hold);
        $this->assertInstanceOf(Numeric::class, $balance->sent);
        $this->assertInstanceOf(Numeric::class, $balance->received);

        $this->assertTrue($balance->equals(100));
        $this->assertTrue($balance->lessThan(101));
        $this->assertTrue($balance->lessThan(100.1));
        $this->assertTrue($balance->lessThan('100.000001'));
        $this->assertTrue($balance->lessThanOrEqual(100));
        $this->assertTrue($balance->lessThanOrEqual(100.0));
        $this->assertTrue($balance->lessThanOrEqual('100.000001'));
        $this->assertTrue($balance->greaterThan(99));
        $this->assertTrue($balance->greaterThan(99.9));
        $this->assertTrue($balance->greaterThan('99.999999'));
        $this->assertTrue($balance->greaterThanOrEqual(100));
        $this->assertTrue($balance->greaterThanOrEqual(100.0));
        $this->assertTrue($balance->greaterThanOrEqual('99.999999'));
        $this->assertTrue($balance->greaterThanOrEqual('100.000000'));

        $this->assertFalse($balance->equals(100.1));
        $this->assertFalse($balance->equals(101));
        $this->assertFalse($balance->lessThan(99));
        $this->assertFalse($balance->lessThan(99.9));
        $this->assertFalse($balance->lessThan('99.999999'));
        $this->assertFalse($balance->lessThanOrEqual(99));
        $this->assertFalse($balance->lessThanOrEqual(99.9));
        $this->assertFalse($balance->lessThanOrEqual('99.999999'));
        $this->assertFalse($balance->greaterThan(100));
        $this->assertFalse($balance->greaterThan(100.0));
        $this->assertFalse($balance->greaterThan('100.000000'));
        $this->assertFalse($balance->greaterThanOrEqual(101));
        $this->assertFalse($balance->greaterThanOrEqual(100.1));
        $this->assertFalse($balance->greaterThanOrEqual('100.000001'));

        $balance->recalculate();

        $this->assertTrue($balance->equals(0));
    }

    public function test_main_value_tracking(): void
    {
        config([
            'wallet.balance.tracking' => [
                'value' => [
                    TransactionStatus::SUCCESS,
                    TransactionStatus::ON_HOLD,
                ],
            ],
        ]);

        /** @var \O21\LaravelWallet\Contracts\Balance $balance */
        [$user, $currency, $balance] = $this->createBalance();

        deposit(self::SMALL_VALUE, $currency)
            ->to($user)
            ->status(TransactionStatus::PENDING)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            0,
        );

        charge(self::SMALL_VALUE, $currency)
            ->from($user)
            ->status(TransactionStatus::ON_HOLD)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            -self::SMALL_VALUE,
        );

        config([
            'wallet.balance.tracking' => [
                'value' => [
                    TransactionStatus::SUCCESS,
                ],
            ],
        ]);

        $balance->recalculate();

        $this->assertBalanceRefreshEquals(
            $balance,
            0,
        );
    }

    public function test_extra_values_tracking(): void
    {
        config([
            'wallet.balance.tracking' => [
                'value_pending' => [
                    TransactionStatus::PENDING,
                ],
                'value_on_hold' => [
                    TransactionStatus::ON_HOLD,
                ],
            ],
        ]);

        /** @var \O21\LaravelWallet\Contracts\Balance $balance */
        [$user, $currency, $balance] = $this->createBalance();

        deposit(self::SMALL_VALUE, $currency)
            ->to($user)
            ->status(TransactionStatus::PENDING)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            self::SMALL_VALUE,
            'value_pending',
        );

        charge(self::SMALL_VALUE, $currency)
            ->from($user)
            ->status(TransactionStatus::ON_HOLD)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            -self::SMALL_VALUE,
            'value_on_hold',
        );
    }

    public function test_tracking_empty_statuses_filter(): void
    {
        config([
            'wallet.balance.tracking' => [
                'value_pending' => [],
            ],
        ]);

        /** @var \O21\LaravelWallet\Contracts\Balance $balance */
        [$user, $currency, $balance] = $this->createBalance();

        deposit(self::SMALL_VALUE, $currency)
            ->to($user)
            ->status(TransactionStatus::PENDING)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            0,
            'value_pending',
        );
    }
}
