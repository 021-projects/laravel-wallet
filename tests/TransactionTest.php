<?php

namespace O21\LaravelWallet\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Exception\FromOrOverchargeRequired;
use O21\LaravelWallet\Exception\InsufficientFundsException;
use O21\LaravelWallet\Tests\Concerns\BalanceSeed;

class TransactionTest extends TestCase
{
    use BalanceSeed;
    use WithFaker;

    public function test_deposit(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        deposit(100, $currency)
            ->to($user)
            ->overcharge()
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
            ->commission(10)
            ->overcharge()
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
            ->overcharge()
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
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals($balance, 100);

        charge(50, $currency)
            ->from($user)
            ->commission(10)
            ->commit();

        $this->assertBalanceRefreshEquals($balance, 50);
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
            ->overcharge()
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
            ->overcharge()
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
            ->overcharge()
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
        $this->assertArrayHasKey('received', $result);

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
            ->overcharge()
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

    public function test_transfer(): void
    {
        [$user, $currency, $balance] = $this->createBalance();
        [$user2] = $this->createBalance();

        transfer(100, $currency)
            ->from($user)
            ->to($user2)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            -100
        );

        $this->assertBalanceRefreshEquals(
            $user2->balance($currency),
            100
        );
    }

    public function test_transfer_update_status(): void
    {
        [$user, $currency, $balance] = $this->createBalance();
        [$user2] = $this->createBalance();

        $tx = transfer(100, $currency)
            ->from($user)
            ->to($user2)
            ->overcharge()
            ->commit();

        $tx->updateStatus(TransactionStatus::PENDING);
        $tx->updateStatus(TransactionStatus::SUCCESS);

        $this->assertBalanceRefreshEquals(
            $balance,
            -100
        );

        $this->assertBalanceRefreshEquals(
            $user2->balance($currency),
            100
        );
    }

    public function test_transfer_with_commission(): void
    {
        [$user, $currency, $balance] = $this->createBalance();
        [$user2] = $this->createBalance();

        transfer(100, $currency)
            ->from($user)
            ->to($user2)
            ->commission(10)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            -100
        );

        $this->assertBalanceRefreshEquals(
            $user2->balance($currency),
            90
        );
    }

    public function test_transfer_not_enough_funds(): void
    {
        [$user, $currency, $balance] = $this->createBalance();
        [$user2] = $this->createBalance();

        $this->expectException(InsufficientFundsException::class);

        transfer(100, $currency)
            ->from($user)
            ->to($user2)
            ->commit();
    }

    public function test_transfer_error_during_send_funds(): void
    {
        [$user, $currency, $balance] = $this->createBalance();
        [$user2] = $this->createBalance();

        $this->expectException(\RuntimeException::class);

        transfer(100, $currency)
            ->from($user)
            ->to($user2)
            ->before(static function () {
                throw new \RuntimeException('test');
            })
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            0
        );
    }

    public function test_no_changes_in_db_if_exception(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $this->expectException(\RuntimeException::class);

        deposit(100, $currency)
            ->to($user)
            ->after(static function () use ($balance) {
                $balance->update(['value' => 999]);
                throw new \RuntimeException('test');
            })
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            0
        );
    }

    public function test_no_processor_exception(): void
    {
        [$user] = $this->createBalance();

        $this->expectException(\RuntimeException::class);

        tx()->to($user)->commit();
    }

    public function test_from_or_overcharge_required(): void
    {
        $this->expectException(FromOrOverchargeRequired::class);

        deposit(100)->commit();
    }

    public function test_something_went_wrong_during_transaction(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $this->expectException(\RuntimeException::class);

        deposit(100, $currency)
            ->to($user)
            ->before(static function () {
                throw new \RuntimeException('test');
            })
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            0
        );
    }

    public function test_transaction_status_accounting_merge(): void
    {
        TransactionStatus::accounting([
            'something',
        ], true);

        $this->assertContains('something', TransactionStatus::accounting());
    }

    public function test_transaction_status_known_merge(): void
    {
        TransactionStatus::known([
            'something',
        ], true);

        $this->assertContains('something', TransactionStatus::known());
    }

    public function test_balance_refresh_after_currency_change(): void
    {
        [$user, $currency, $balance] = $this->createBalance();
        $newCurrency = $this->faker->currencyCode();

        $transaction = deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->commit();

        $this->assertBalanceRefreshEquals(
            $balance,
            100
        );

        $transaction->currency = $newCurrency;
        $transaction->save();

        $this->assertBalanceRefreshEquals(
            $balance,
            0
        );
        $this->assertEquals(
            100,
            $user->balance($newCurrency)->value->get()
        );
    }
}
