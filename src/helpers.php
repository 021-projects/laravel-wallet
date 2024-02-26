<?php

use O21\LaravelWallet\Contracts\Converter;
use O21\LaravelWallet\Contracts\Custodian;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Numeric;

if (! function_exists('num')) {
    /**
     * Create a new Numeric instance for safe calculations
     */
    function num(string|float|int|Numeric $value, ?int $scale = null): Numeric
    {
        return new Numeric($value, $scale);
    }
}

if (! function_exists('tx')) {
    function tx(
        string|float|int|Numeric|null $amount = null,
        ?string $currency = null
    ): TransactionCreator {
        $creator = app(TransactionCreator::class);

        if ($amount) {
            $creator->amount($amount);
        }

        if ($currency) {
            $creator->currency($currency);
        }

        return $creator;
    }
}

if (! function_exists('deposit')) {
    function deposit(
        string|float|int|Numeric $amount,
        ?string $currency = null
    ): TransactionCreator {
        return tx($amount, $currency)->processor('deposit');
    }
}

if (! function_exists('charge')) {
    function charge(
        string|float|int|Numeric $amount,
        ?string $currency = null
    ): TransactionCreator {
        return tx($amount, $currency)->processor('charge');
    }
}

if (! function_exists('conversion')) {
    function conversion(
        string|float|int|Numeric|null $amount = null,
        ?string $currency = null
    ): Converter {
        $converter = app(Converter::class);

        if ($amount) {
            $converter->amount($amount);
        }

        if ($currency) {
            $converter->from($currency);
        }

        return $converter;
    }
}

if (! function_exists('transfer')) {
    function transfer(
        string|float|int|Numeric $amount,
        ?string $currency = null
    ): TransactionCreator {
        return tx($amount, $currency)->processor('transfer');
    }
}

if (! function_exists('get_custodian')) {
    function get_custodian(?string $name = null, array $meta = []): Custodian
    {
        return app(Custodian::class)::of($name, $meta);
    }
}
