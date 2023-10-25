<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Casts\Attribute;
use O21\LaravelWallet\Enums\TransactionStatus;

/**
 * @property-read ?\O21\LaravelWallet\Contracts\TransactionProcessor $processor
 */
interface Transaction
{
    public function toApi(): array;

    public function hasStatus(TransactionStatus|string $status): bool;
    public function updateStatus(TransactionStatus|string $status): bool;

    public function getMeta(string $key = null, $default = null);
    public function setMeta(
        array|string $key,
        float|array|int|string $value = null
    ): void;
    public function updateMeta(
        array|string $key,
        float|array|int|string $value = null
    ): bool;

    public function getRelatedBalance(): ?Balance;
    public function getTotal(): string;

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute<\O21\LaravelWallet\Contracts\TransactionProcessor>
     */
    public function processor(): Attribute;
}
