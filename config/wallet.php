<?php

return [
    'default_currency' => 'USD',

    'balance' => [
        'accounting_statuses' => [
            \O21\LaravelWallet\Enums\TransactionStatus::SUCCESS,
            \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD
        ],
        'extra_values'        => [
            // enable value_pending calculation
            'pending' => false,
            // enable value_on_hold calculation
            'on_hold' => false,
        ],
        'max_scale'           => 8,
        'log_states'          => false,
    ],

    'models' => [
        'balance'       => \O21\LaravelWallet\Models\Balance::class,
        'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
        'transaction'   => \O21\LaravelWallet\Models\Transaction::class,
    ],

    'table_names' => [
        'balances'       => 'balances',
        'balance_states' => 'balance_states',
        'transactions'   => 'transactions',
    ],

    'processors' => [
        'deposit'  => \O21\LaravelWallet\Transaction\Processors\DepositProcessor::class,
        'charge'   => \O21\LaravelWallet\Transaction\Processors\ChargeProcessor::class,
        'transfer' => \O21\LaravelWallet\Transaction\Processors\TransferProcessor::class,
    ],
];
