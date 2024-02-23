<?php

namespace O21\LaravelWallet\Tests;

use O21\LaravelWallet\Contracts\ShadowBalance as ShadowBalanceContract;
use O21\LaravelWallet\Models\ShadowBalance;

class ShadowBalanceTest extends TestCase
{
    private const MY_UUID = 'gg';

    public function test_interface(): void
    {
        $shadow = ShadowBalance::of(self::MY_UUID);

        $this->assertInstanceOf(ShadowBalanceContract::class, $shadow);
        $this->assertEquals(self::MY_UUID, $shadow->uuid);
        $this->assertModelExists($shadow);

        $shadow = ShadowBalance::of();
        $this->assertModelExists($shadow);
        $this->assertMatchesRegularExpression('/[a-z0-9-]{36}/', $shadow->uuid);
    }

    public function test_created_only_once(): void
    {
        $shadow = ShadowBalance::of(self::MY_UUID);
        $shadow2 = ShadowBalance::of(self::MY_UUID);
        $this->assertEquals($shadow->id, $shadow2->id);
    }

    public function test_deposit(): void
    {
        $shadow = ShadowBalance::of(self::MY_UUID);

        $tx = deposit(100, 'USD')
            ->to($shadow)
            ->overcharge()
            ->commit();

        $this->assertModelExists($tx);

        $this->assertEquals(100, (string) $shadow->balance()->fresh()->value);
    }

    public function test_transfer(): void
    {
        $shadow = ShadowBalance::of(self::MY_UUID);
        $shadow2 = ShadowBalance::of();

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
        $this->assertEquals(ShadowBalance::class, app(ShadowBalanceContract::class)::class);
    }
}
