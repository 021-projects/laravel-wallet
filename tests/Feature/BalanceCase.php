<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Concerns\BalanceTest;
use Tests\TestCase;

class BalanceCase extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    use BalanceTest;

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
}
