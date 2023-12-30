<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read ?\O21\LaravelWallet\Contracts\Payable $from
 * @property-read ?\O21\LaravelWallet\Contracts\Payable $to
 * @property-read ?\O21\LaravelWallet\Contracts\TransactionProcessor $processor
 */
interface Transaction
{
    public function toApi(): array;

    public function hasStatus(string $status): bool;
    public function updateStatus(string $status): bool;

    public function getMeta(string $key = null, $default = null);
    public function setMeta(
        array|string $key,
        float|array|int|string $value = null
    ): void;
    public function updateMeta(
        array|string $key,
        float|array|int|string $value = null
    ): bool;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\O21\LaravelWallet\Contracts\Payable>
     */
    public function from(): MorphTo;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\O21\LaravelWallet\Contracts\Payable>
     */
    public function to(): MorphTo;

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<\O21\LaravelWallet\Contracts\TransactionProcessor>
     */
    public function processor(): Attribute;
}
