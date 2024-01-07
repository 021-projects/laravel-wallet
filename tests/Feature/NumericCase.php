<?php

namespace Tests\Feature;

use Tests\TestCase;

class NumericCase extends TestCase
{
    private const DIGITS_18 = 0.000000000000000001;
    private const DIGITS_8 = 0.00000025;

    public function test_digits_18(): void
    {
        config()->set('wallet.balance.max_scale', 18);

        $num = num(self::DIGITS_18);
        $num2 = num(self::DIGITS_18);

        $sum = $num->add($num2)->get();

        $this->assertEquals('0.000000000000000002', $sum);

        $sub = $num->sub($num2)->get();

        $this->assertEquals('0.000000000000000001', $sub);
    }

    public function test_digits_18_with_8_max_scale(): void
    {
        config()->set('wallet.balance.max_scale', 8);

        $num = num(self::DIGITS_18);
        $num2 = num(self::DIGITS_18);

        $sum = $num->add($num2)->get();

        $this->assertEquals('0', $sum);

        $sub = $num->sub($num2)->get();

        $this->assertEquals('0', $sub);
    }

    public function test_if_max_scale_null(): void
    {
        config()->set('wallet.balance.max_scale', null);

        $num = num(self::DIGITS_8);
        $num2 = num(self::DIGITS_8);

        $sum = $num->add($num2)->get();

        $this->assertEquals('0.0000005', $sum);

        $sub = $num->sub($num2)->get();

        $this->assertEquals('0.00000025', $sub);
    }
}
