<?php

namespace O21\LaravelWallet\Tests;

use O21\LaravelWallet\Contracts\Custodian as CustodianContract;
use O21\LaravelWallet\Models\Custodian;

class CustodianTest extends TestCase
{
    private const MY_UUID = 'gg';

    public function test_interface(): void
    {
        $shadow = Custodian::of(self::MY_UUID);

        $this->assertInstanceOf(CustodianContract::class, $shadow);
        $this->assertEquals(self::MY_UUID, $shadow->name);
        $this->assertModelExists($shadow);

        $shadow = Custodian::of();
        $this->assertModelExists($shadow);
        $this->assertMatchesRegularExpression('/[a-z0-9-]{36}/', $shadow->name);

        $shadow = Custodian::of(self::MY_UUID, ['a' => 1]);

        $this->assertEquals(1, $shadow->meta['a']);

        $shadow = Custodian::of('random_new', ['a' => 2]);

        $this->assertEquals('random_new', $shadow->name);
        $this->assertEquals(2, $shadow->meta['a']);
    }

    public function test_created_only_once(): void
    {
        $shadow = Custodian::of(self::MY_UUID);
        $shadow2 = Custodian::of(self::MY_UUID);
        $this->assertEquals($shadow->id, $shadow2->id);
    }

    public function test_deposit(): void
    {
        $shadow = Custodian::of(self::MY_UUID);

        $tx = deposit(100, 'USD')
            ->to($shadow)
            ->overcharge()
            ->commit();

        $this->assertModelExists($tx);

        $this->assertEquals(100, (string) $shadow->balance()->fresh()->value);
    }

    public function test_transfer(): void
    {
        $shadow = Custodian::of(self::MY_UUID);
        $shadow2 = Custodian::of();

        $tx = deposit(100, 'USD')
            ->to($shadow)
            ->overcharge()
            ->commit();

        $this->assertModelExists($tx);

        $tx = transfer(50, 'USD')
            ->from($shadow)
            ->to($shadow2)
            ->overcharge()
            ->commit();

        $this->assertModelExists($tx);

        $this->assertEquals(50, (string) $shadow->balance()->fresh()->value);
        $this->assertEquals(50, (string) $shadow2->balance()->fresh()->value);
    }

    public function test_model_from_contract(): void
    {
        $this->assertEquals(Custodian::class, app(CustodianContract::class)::class);
    }

    public function test_get_custodian_helper(): void
    {
        $shadow = get_custodian(self::MY_UUID);
        $this->assertInstanceOf(Custodian::class, $shadow);
        $this->assertEquals(self::MY_UUID, $shadow->name);
        $this->assertModelExists($shadow);

        $shadow = get_custodian();
        $this->assertModelExists($shadow);
        $this->assertMatchesRegularExpression('/[a-z0-9-]{36}/', $shadow->name);
    }
}
