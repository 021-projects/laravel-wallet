<?php

return [
    'default_currency' => 'USD',

    'balance' => [
        'extra_values' => [
            // enable value_pending calculation
            'pending' => false,
            // enable value_on_hold calculation
            'on_hold' => false,
        ],
        'accounting_statuses' => [
            \O21\LaravelWallet\Enums\TransactionStatus::SUCCESS,
            \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD
        ],
    ],

    'models' => [
        'user'        => \App\Models\User::class,
        'balance'     => \O21\LaravelWallet\Models\Balance::class,
        'transaction' => \O21\LaravelWallet\Models\Transaction::class,
    ],

    'table_names' => [
        'balances'     => 'balances',
        'transactions' => 'transactions',
    ],

    'processors' => [
        'deposit'  => \O21\LaravelWallet\Transaction\Processors\DepositProcessor::class,
        'charge'   => \O21\LaravelWallet\Transaction\Processors\ChargeProcessor::class,
        'transfer' => \O21\LaravelWallet\Transaction\Processors\TransferProcessor::class,
    ],
];
