<?php

namespace O21\LaravelWallet\Contracts;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read ?\O21\LaravelWallet\Contracts\Payable $from
 * @property-read ?\O21\LaravelWallet\Contracts\Payable $to
 * @property-read ?\O21\LaravelWallet\Contracts\TransactionProcessor $processor
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface Transaction extends Metable
{
    public function toApi(...$opts): array;

    public function hasStatus(string $status): bool;

    public function updateStatus(string $status): bool;

    public function recalculateBalances(): void;

    public function normalizeNumbers(): void;

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

    public function nextBatch(): int;

    public function logStates(): void;

    public function deleteStates(): void;
}
