<?php

namespace O21\LaravelWallet\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Exception\FromOrOverchargeRequired;
use O21\LaravelWallet\Exception\ImplicitTransactionMergeAttemptException;
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

    public function test_received_changed_when_amount_changed(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $tx = deposit(100, $currency)
            ->commission(10)
            ->to($user)
            ->overcharge()
            ->commit();

        $this->assertEquals(
            90,
            $tx->received
        );

        $tx->amount = 50;
        $tx->save();

        $this->assertEquals(
            40,
            $tx->fresh()->received
        );
    }

    public function test_creator_args_resolving(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->before(function ($creator, $tx) {
                $this->assertInstanceOf(TransactionCreator::class, $creator);
                $this->assertInstanceOf(Transaction::class, $tx);
            })
            ->after(function ($creator, $tx) {
                $this->assertInstanceOf(TransactionCreator::class, $creator);
                $this->assertInstanceOf(Transaction::class, $tx);
            })
            ->commit();

        deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->before(function ($tx) {
                $this->assertInstanceOf(Transaction::class, $tx);
            })
            ->after(function ($tx) {
                $this->assertInstanceOf(Transaction::class, $tx);
            })
            ->commit();
    }

    public function test_batch(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $depositCounts = 5;
        $txs = [];

        for ($i = 0; $i < $depositCounts; $i++) {
            $txs[$i] = deposit(100, $currency)
                ->to($user)
                ->overcharge()
                ->commit();
        }

        $this->assertNotEmpty($txs);

        for ($i = 0; $i < $depositCounts; $i++) {
            $this->assertEquals(
                $i + 1,
                $txs[$i]->batch
            );
        }
    }

    public function test_next_batch_jump(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $tx = deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->batch(100)
            ->commit();

        $this->assertEquals(
            100,
            $tx->batch
        );

        $tx = deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->commit();

        $this->assertEquals(
            101,
            $tx->batch
        );
    }

    public function test_batch_exists_exception(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $tx = deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->batch(100, true)
            ->commit();

        $this->assertEquals(
            100,
            $tx->batch
        );

        $this->expectException(ImplicitTransactionMergeAttemptException::class);

        deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->batch(100, false)
            ->commit();
    }

    public function test_add_to_batch(): void
    {
        [$user, $currency, $balance] = $this->createBalance();

        $tx = deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->batch(100)
            ->commit();

        $this->assertEquals(
            100,
            $tx->batch
        );

        $tx = deposit(100, $currency)
            ->to($user)
            ->overcharge()
            ->batch(100, exists: true)
            ->commit();

        $this->assertEquals(
            100,
            $tx->batch
        );

        $txsCount = app(Transaction::class)->where('batch', 100)->count();

        $this->assertEquals(2, $txsCount);
    }

    public function test_currency_scaling(): void
    {
        [$user] = $this->createBalance();

        config(['wallet.currency_scaling.USD' => 2]);

        $tx = deposit(0.000001, 'USD')
            ->to($user)
            ->overcharge()
            ->commit();

        $this->assertEquals(
            0.00,
            $tx->amount
        );

        config(['wallet.currency_scaling.USD' => 8]);

        $tx = deposit(0.00000001, 'USD')
            ->to($user)
            ->overcharge()
            ->commit();

        $this->assertEquals(
            0.00000001,
            $tx->amount
        );
    }

    public function test_batch_neighbours(): void
    {
        [$user] = $this->createBalance();

        $depositsCount = 3;

        for ($i = 0; $i < $depositsCount; $i++) {
            $tx = deposit(100, 'USD')
                ->to($user)
                ->overcharge()
                ->batch(1, exists: true)
                ->commit();
        }

        $neighbours = $tx->neighbours;

        $this->assertNotEmpty($neighbours);

        $this->assertInstanceOf(Collection::class, $neighbours);

        $this->assertCount($depositsCount - 1, $neighbours);

        $neighbours->each(function ($neighbour) use ($tx) {
            $this->assertNotEquals($neighbour->id, $tx->id);
        });
    }
}
