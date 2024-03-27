<?php

namespace O21\LaravelWallet\ConfigHelpers;

function balance_extra_values(): array
{
    return config('wallet.balance.extra_values', []);
}

function log_balance_states_enabled(): bool
{
    return config('wallet.balance.log_states', false);
}

function table_name(string $key): string
{
    return config("wallet.table_names.$key", $key) ?? $key;
}

function get_model_class(string $key): ?string
{
    $defaultModelsMap = [
        'balance' => \O21\LaravelWallet\Models\Balance::class,
        'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
        'custodian' => \O21\LaravelWallet\Models\Custodian::class,
        'transaction' => \O21\LaravelWallet\Models\Transaction::class,
    ];

    return config("wallet.models.$key") ?? $defaultModelsMap[$key] ?? null;
}

function default_currency(): string
{
    return config('wallet.default_currency');
}

function tx_accounting_statuses(): array
{
    return config('wallet.balance.accounting_statuses', []);
}

function tx_currency_scaling(string $currency): int
{
    return config("wallet.transactions.currency_scaling.{$currency}", 8);
}

function tx_route_key(): string
{
    return config('wallet.transactions.route_key', 'uuid') ?? 'uuid';
}

function tx_processors(): array
{
    return config('wallet.processors', []);
}

function get_tx_processor_class($key): ?string
{
    $key = (string) $key;

    return config("wallet.processors.$key");
}

function num_precise_scale(): int
{
    return config('wallet.numeric.precise_scale', 22) ?? 22;
}

function num_rounding_mode(): int
{
    return config('wallet.numeric.rounding_mode', \Brick\Math\RoundingMode::DOWN);
}
