<?php

namespace O21\LaravelWallet\ConfigHelpers;

function currency_scale(string $currency): int
{
    return config("wallet.currency_scaling.{$currency}", balance_max_scale());
}

function balance_max_scale(): int
{
    return config('wallet.balance.max_scale', 8);
}

function table_name(string $key): string
{
    return config("wallet.table_names.$key", $key) ?? $key;
}
