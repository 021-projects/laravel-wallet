<?php

namespace O21\LaravelWallet\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use InvalidArgumentException;
use O21\LaravelWallet\Contracts\Exchanger;
use O21\LaravelWallet\Contracts\Transaction as ITransaction;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Exception\InsufficientFundsException;
use O21\LaravelWallet\Tests\Concerns\BalanceSeed;
use Workbench\Database\Factories\UserFactory;

class ExchangerTest extends TestCase
{
    use BalanceSeed;
    use WithFaker;

    private const BTC_AMOUNT = 0.01;
    private const BTC_RATE = 50_000;
    private const BTC_COMMISSION = 0.001 * 0.01;
    private const USD_COMMISSION = 25;
    private const GENIUS_NOTE = 'BTC to USD exchange';

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

        $txs = $this->btcExchange()
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
        $this->assertEquals('exchange_debit', $debitTx->processor_id);
        $this->assertEquals('exchange_credit', $creditTx->processor_id);

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

        $txs = $this->btcExchange()
            ->before(function (
                Exchanger $exchanger,
                TransactionCreator $debitTxCreator,
                TransactionCreator $creditTxCreator
            ) use ($newAmount, $newCommission, $smallFee) {
                $exchanger->amount($newAmount)->commission(
                    src: $newCommission,
                    dest: $smallFee
                )->rate(self::BTC_RATE * 2);

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

        $this->btcExchange()
            ->after(function (
                Exchanger $exchanger,
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

        $this->btcExchange()->performOn($user);
    }

    public function test_overcharge_allowed(): void
    {
        $user = UserFactory::new()->create();

        $this->btcExchange()->overcharge()->performOn($user);

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

        exchange()->performOn($user);

        $this->expectException(InvalidArgumentException::class);

        exchange(0, 'BTC')->to('USD')->performOn($user);

        $this->expectException(InvalidArgumentException::class);

        $this->btcExchange()->to('USD')->rate(0)->performOn($user);

        $this->expectException(InvalidArgumentException::class);

        $this->btcExchange()->to('BTC')->performOn($user);
    }

    protected function btcExchange(): Exchanger
    {
        return exchange(self::BTC_AMOUNT, 'BTC')
            ->to('USD')
            ->rate(self::BTC_RATE);
    }
}