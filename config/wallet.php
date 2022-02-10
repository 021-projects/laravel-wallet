<?php

return [
    'models' => [
        'user' => 'App\Models\User',
        'balance' => \O21\LaravelWallet\Models\Balance::class,
        'transaction' => \O21\LaravelWallet\Models\Transaction::class,
    ],

    'observers' => [
        'transaction' => \O21\LaravelWallet\Observers\TransactionObserver::class,
    ],

    'table_names' => [
        'balances' => 'balances',
        'transactions' => 'transactions',
    ],

    'handlers' => [
        'replenishment' => \O21\LaravelWallet\TransactionHandlers\ReplenishmentHandler::class,
        'write_off'     => \O21\LaravelWallet\TransactionHandlers\WriteOffHandler::class,
    ],

    'currencies' => [
        'basic' => 'USD',

        /**
         * Is convert all another currencies to basic currency
         */
        'convert' => false,

        /**
         * Currency converter class
         * Must implement \O21\LaravelWallet\Contracts\CurrencyConverterContract contract
         */
        'converter' => null,

        /**
         * List of currencies which will be ignored for converting
         */
        'dont_convert' => [],
    ]
];
