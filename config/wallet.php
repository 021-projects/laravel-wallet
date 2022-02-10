<?php

return [
    'models' => [
        'user' => 'App\Models\User',
        'balance' => \O21\LaravelWallet\Models\Balance::class,
        'transaction' => \O21\LaravelWallet\Models\Transaction::class,
    ],

    'table_names' => [
        'balances' => 'balances',
        'transactions' => 'transactions',
    ]
];
