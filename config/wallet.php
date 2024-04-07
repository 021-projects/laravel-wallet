<?php

return [
    'default_currency' => 'USD',

    'balance' => [
        'tracking' => [
            // The main value of the balance (aka confirmed/available)
            // Transactions with following statuses will be included in the recalculation
            'value' => [
                \O21\LaravelWallet\Enums\TransactionStatus::SUCCESS,
                \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD,
                \O21\LaravelWallet\Enums\TransactionStatus::IN_PROGRESS,
                \O21\LaravelWallet\Enums\TransactionStatus::AWAITING_APPROVAL,
            ],
            // The value of the balance that is pending
            // If empty, value will not be tracking
            'value_pending' => [
                // \O21\LaravelWallet\Enums\TransactionStatus::PENDING,
            ],
            // The value of the balance that is holding
            // If empty, value will not be tracking
            'value_on_hold' => [
                // \O21\LaravelWallet\Enums\TransactionStatus::ON_HOLD,
            ],
        ],
        'log_states' => false,
    ],

    'models' => [
        'balance' => \O21\LaravelWallet\Models\Balance::class,
        'balance_state' => \O21\LaravelWallet\Models\BalanceState::class,
        'custodian' => \O21\LaravelWallet\Models\Custodian::class,
        'transaction' => \O21\LaravelWallet\Models\Transaction::class,
    ],

    'table_names' => [
        'balances' => 'balances',
        'balance_states' => 'balance_states',
        'custodians' => 'custodians',
        'transactions' => 'transactions',
    ],

    'transactions' => [
        'currency_scaling' => [
            'USD' => 2,
            'EUR' => 2,
            'BTC' => 8,
            'ETH' => 8,
        ],

        'route_key' => 'uuid',
    ],

    'processors' => [
        'deposit' => \O21\LaravelWallet\Transaction\Processors\DepositProcessor::class,
        'charge' => \O21\LaravelWallet\Transaction\Processors\ChargeProcessor::class,
        'conversion_credit' => \O21\LaravelWallet\Transaction\Processors\ConversionCreditProcessor::class,
        'conversion_debit' => \O21\LaravelWallet\Transaction\Processors\ConversionDebitProcessor::class,
        'transfer' => \O21\LaravelWallet\Transaction\Processors\TransferProcessor::class,
    ],

    'numeric' => [
        // The scale for a numbers in the operations with precise calculations required
        // (like division, multiplication, etc.)
        'precise_scale' => 22,

        'rounding_mode' => \Brick\Math\RoundingMode::DOWN,
    ]
];
