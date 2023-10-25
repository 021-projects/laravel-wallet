<?php

return [
    'default_currency' => 'USD',

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
