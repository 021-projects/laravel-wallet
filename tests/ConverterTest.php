<?php

namespace O21\LaravelWallet\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use InvalidArgumentException;
use O21\LaravelWallet\Contracts\Converter;
use O21\LaravelWallet\Contracts\Transaction as ITransaction;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Exception\ImplicitTxMergeAttemptException;
use O21\LaravelWallet\Exception\InsufficientFundsException;
use O21\LaravelWallet\Tests\Concerns\BalanceSeed;
use Workbench\Database\Factories\UserFactory;

class ConverterTest extends TestCase
{
    use BalanceSeed;
    use WithFaker;

    private const BTC_AMOUNT = 0.01;

    private const BTC_RATE = 50_000;

    private const BTC_COMMISSION = 0.001 * 0.01;

    private const USD_COMMISSION = 25;

    private const GENIUS_NOTE = 'BTC to USD conversion haha';

    public function test_interface(): void
    {
        $user = UserFactory::new()->create();

        deposit(self::BTC_AMOUNT, 'BTC')->to($user)->overcharge()->commit();

        $btcSpent = num(self::BTC_AMOUNT)->sub(self::BTC_COMMISSION);
        $exceptedBTC = 0;
        $exceptedUSD = num($btcSpent)
            ->mul(self::BTC_RATE)
            ->sub(self::USD_COMMISSION)
            ->get();

        $txs = $this->btcConversion()
            ->commission(
                src : self::BTC_COMMISSION, // take 1% of BTC
                dest: self::USD_COMMISSION // and take 25 USD
            )
            ->meta(['note' => self::GENIUS_NOTE])
            ->performOn($user);

        $this->assertBalanceRefreshEquals(
            $user->balance('BTC'),
            $exceptedBTC
        );

        $this->assertBalanceRefreshEquals(
            $user->balance('USD'),
            $exceptedUSD
        );

        $debitTx = $txs->get('debit');
        $creditTx = $txs->get('credit');

        $this->assertInstanceOf(ITransaction::class, $debitTx);
        $this->assertInstanceOf(ITransaction::class, $creditTx);

        $this->assertEquals('BTC', $debitTx->currency);
        $this->assertEquals('USD', $creditTx->currency);
        $this->assertEquals('conversion_debit', $debitTx->processor_id);
        $this->assertEquals('conversion_credit', $creditTx->processor_id);

        $this->assertEquals(self::BTC_AMOUNT, $debitTx->amount);
        $this->assertEquals(
            num($btcSpent)->mul(self::BTC_RATE)->get(),
            $creditTx->amount
        );

        $this->assertEquals(self::BTC_COMMISSION, $debitTx->commission);
        $this->assertEquals(self::USD_COMMISSION, $creditTx->commission);

        $this->assertEquals(self::GENIUS_NOTE, $debitTx->meta['note']);
        $this->assertEquals(self::GENIUS_NOTE, $creditTx->meta['note']);
    }

    public function test_interface_before(): void
    {
        $user = UserFactory::new()->create();

        deposit(self::BTC_AMOUNT, 'BTC')->to($user)->overcharge()->commit();

        $newAmount = num(self::BTC_AMOUNT)->div(2);
        $newCommission = num($newAmount)->mul(0.01);
        $smallFee = 0.02;

        $txs = $this->btcConversion()
            ->before(function (
                Converter $converter,
                TransactionCreator $debitTxCreator,
                TransactionCreator $creditTxCreator
            ) use ($newAmount, $newCommission, $smallFee) {
                $converter->amount($newAmount)->commission(
                    src: $newCommission,
                    dest: $smallFee
                )->at(self::BTC_RATE * 2);

                $debitTxCreator->meta(['debit_test' => true]);
                $creditTxCreator->meta(['credit_test' => true]);
            })
            ->performOn($user);

        $debitTx = $txs->get('debit');
        $creditTx = $txs->get('credit');

        $this->assertBalanceRefreshEquals(
            $user->balance('BTC'),
            num(self::BTC_AMOUNT)->sub($newAmount)->get()
        );

        $this->assertBalanceRefreshEquals(
            $user->balance('USD'),
            num($newAmount)->sub($newCommission)->mul(self::BTC_RATE * 2)->sub($smallFee)->get()
        );

        $this->assertEquals($newAmount, $debitTx->amount);
        $this->assertEquals(num($newAmount)->sub($newCommission)->mul(self::BTC_RATE * 2), $creditTx->amount);
        $this->assertEquals($newCommission, $debitTx->commission);
        $this->assertEquals($smallFee, $creditTx->commission);

        $this->assertTrue($debitTx->meta['debit_test']);
        $this->assertTrue($creditTx->meta['credit_test']);
    }

    public function test_interface_after(): void
    {
        $user = UserFactory::new()->create();

        deposit(self::BTC_AMOUNT, 'BTC')->to($user)->overcharge()->commit();

        $called = false;

        $this->btcConversion()
            ->after(function (
                Converter $converter,
                ITransaction $debitTx,
                ITransaction $creditTx
            ) use (&$called) {
                $called = true;
            })
            ->performOn($user);

        $this->assertTrue($called);
    }

    public function test_insufficient_funds(): void
    {
        $user = UserFactory::new()->create();

        $this->expectException(InsufficientFundsException::class);

        $this->btcConversion()->performOn($user);
    }

    public function test_overcharge_allowed(): void
    {
        $user = UserFactory::new()->create();

        $this->btcConversion()->overcharge()->performOn($user);

        $this->assertBalanceRefreshEquals(
            $user->balance('BTC'),
            -self::BTC_AMOUNT
        );

        $this->assertBalanceRefreshEquals(
            $user->balance('USD'),
            self::BTC_AMOUNT * self::BTC_RATE
        );
    }

    public function test_throw_invalid_arguments(): void
    {
        $user = UserFactory::new()->create();

        $this->expectException(InvalidArgumentException::class);

        conversion()->performOn($user);

        $this->expectException(InvalidArgumentException::class);

        conversion(0, 'BTC')->to('USD')->performOn($user);

        $this->expectException(InvalidArgumentException::class);

        $this->btcConversion()->to('USD')->at(0)->performOn($user);

        $this->expectException(InvalidArgumentException::class);

        $this->btcConversion()->to('BTC')->performOn($user);
    }

    public function test_batch(): void
    {
        $user = UserFactory::new()->create();

        $lastBatch = deposit(100, 'BTC')->to($user)
            ->overcharge()
            ->commit()
            ->batch;

        $conversionCounts = 5;
        $conversions = [];

        for ($i = 0; $i < $conversionCounts; $i++) {
            $conversions[] = $this->btcConversion()->performOn($user);
        }

        $this->assertNotEmpty($conversions);

        for ($i = 0; $i < $conversionCounts; $i++) {
            $txs = $conversions[$i];
            $debitTx = $txs->get('debit');
            $creditTx = $txs->get('credit');
            $this->assertInstanceOf(ITransaction::class, $debitTx);
            $this->assertInstanceOf(ITransaction::class, $creditTx);
            $this->assertEquals($lastBatch + $i + 1, $debitTx->batch);
            $this->assertEquals($lastBatch + $i + 1, $creditTx->batch);
        }
    }

    public function test_next_batch_jump(): void
    {
        $user = UserFactory::new()->create();

        deposit(100, 'BTC')->to($user)
            ->overcharge()
            ->commit();

        $this->btcConversion()
            ->batch(100)
            ->performOn($user);

        $nextBatch = $this->btcConversion()
            ->performOn($user)
            ->get('debit')
            ->batch;

        $this->assertEquals(101, $nextBatch);
    }

    public function test_batch_exists_exception(): void
    {
        $user = UserFactory::new()->create();

        deposit(100, 'BTC')->to($user)
            ->overcharge()
            ->commit();

        $this->expectException(ImplicitTxMergeAttemptException::class);

        $this->btcConversion()
            ->batch(1)
            ->performOn($user);
    }

    public function test_add_to_batch(): void
    {
        $user = UserFactory::new()->create();

        deposit(100, 'BTC')->to($user)
            ->overcharge()
            ->commit();

        $txs = $this->btcConversion()
            ->batch(1, exists: true)
            ->performOn($user);

        $this->assertEquals(1, $txs->get('debit')->batch);
        $this->assertEquals(1, $txs->get('credit')->batch);

        $this->assertEquals(
            3,
            app(ITransaction::class)->where('batch', 1)->count()
        );
    }

    public function test_batch_status_update(): void
    {
        $user = UserFactory::new()->create();

        deposit(100, 'BTC')->to($user)
            ->overcharge()
            ->commit();

        $txs = $this->btcConversion()
            ->performOn($user);

        $debitTx = $txs->get('debit');
        $creditTx = $txs->get('credit');

        $debitTx->updateStatus(TransactionStatus::FAILED);

        $debitTx = $debitTx->fresh();
        $creditTx = $creditTx->fresh();

        $this->assertEquals(
            TransactionStatus::FAILED,
            $debitTx->status
        );

        $this->assertEquals(
            TransactionStatus::FAILED,
            $creditTx->status
        );

        $creditTx->updateStatus(TransactionStatus::SUCCESS);

        $debitTx = $debitTx->fresh();
        $creditTx = $creditTx->fresh();

        $this->assertEquals(
            TransactionStatus::SUCCESS,
            $debitTx->status
        );

        $this->assertEquals(
            TransactionStatus::SUCCESS,
            $creditTx->status
        );
    }

    public function test_large_scale_rate_invalid(): void
    {
        $user = UserFactory::new()->create();

        deposit(20, 'KZT')->to($user)
            ->overcharge()
            ->commit();

        $btcKztRate = 500_141_245.21;

        // scale is 8, so 1 / $btcKztRate will be 0 (must be: 0.000000001999435)

        $this->expectException(InvalidArgumentException::class);

        conversion(20, 'KZT')
            ->to('BTC')
            ->at(1 / $btcKztRate)
            ->performOn($user);
    }

    public function test_large_scale_rate(): void
    {
        $user = UserFactory::new()->create();

        deposit(20, 'KZT')->to($user)
            ->overcharge()
            ->commit();

        $btcKztRate = 500_141_245.21;

        // scale is 15, so 1 / $btcKztRate will be 0.000000001999435

        $txs = conversion(20, 'KZT')
            ->to('BTC')
            ->at(num(1, 15)->div($btcKztRate))
            ->performOn($user);

        $this->assertBalanceRefreshEquals(
            $user->balance('KZT'),
            0
        );

        $this->assertBalanceRefreshEquals(
            $user->balance('BTC'),
            0.00000003 // 0.000000001999435 * 20 with scale 8
        );

        $txs->each(function (ITransaction $tx) {
            $this->assertModelExists($tx);
            $this->assertTrue($tx->hasStatus(TransactionStatus::SUCCESS));
        });
    }

    protected function btcConversion(): Converter
    {
        return conversion(self::BTC_AMOUNT, 'BTC')
            ->to('USD')
            ->at(self::BTC_RATE);
    }
}
