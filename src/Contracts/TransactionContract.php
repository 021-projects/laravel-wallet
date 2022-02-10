<?php

namespace O21\LaravelWallet\Contracts;

interface TransactionContract
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FROZEN = 'frozen';

    public static function create(
        string $handler,
        UserContract $user,
        float $amount,
        string $currency,
        float $commission = 0,
        array $data = []
    ): TransactionContract;

    public function toWalletTransaction(): array;

    public function prepare(): void;

    public function convertToBasicCurrency(): void;
}
