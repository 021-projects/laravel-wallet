<?php

namespace O21\LaravelWallet\Tests\Feature;

use O21\LaravelWallet\Tests\TestCase;

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

    public function test_min(): void
    {
        $min = '0.00000025';

        $num = num($min);

        $biggerNumbers = [];
        for ($i = 0; $i < 10; $i++) {
            $biggerNumbers[] = (clone $num)->add(num('0.00000001')->mul($i))->get();
        }

        $this->assertEquals($min, $num->min($biggerNumbers[0])->get());
        $this->assertEquals($min, $num->min(...$biggerNumbers)->get());
        $this->assertEquals($min, $num->min('0.00000026')->get());
        $this->assertEquals($min, $num->min(0.00000026)->get());
        $this->assertEquals($min, $num->min(1)->get());
        $this->assertEquals('0.00000024', $num->min('0.00000024')->get());
    }

    public function test_max(): void
    {
        $max = '0.00000025';

        $num = num($max);

        $smallerNumbers = [];
        for ($i = 0; $i < 10; $i++) {
            $smallerNumbers[] = (clone $num)->sub(num('0.00000001')->mul($i))->get();
        }

        $this->assertEquals($max, $num->max($smallerNumbers[0])->get());
        $this->assertEquals($max, $num->max(...$smallerNumbers)->get());
        $this->assertEquals($max, $num->max('0.00000024')->get());
        $this->assertEquals($max, $num->max(0.00000024)->get());
        $this->assertEquals(1, $num->max(1)->get());
    }
}
