<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

interface TransactionContract
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FROZEN = 'frozen';

    public static function create(
        string $handler,
        UserContract $user,
        string $amount,
        string $currency,
        string $commission = '0',
        array $data = [],
        Model|Builder $queryForLock = null,
        callable $before = null,
        callable $after = null
    ): TransactionContract;

    public function toWalletTransaction(): array;

    public function prepare(): void;

    public function convertToBasicCurrency(): void;
}
