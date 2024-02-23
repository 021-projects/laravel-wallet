<?php

namespace O21\LaravelWallet\Contracts;

/**
 * @mixin \O21\LaravelWallet\Models\ShadowBalance
 */
interface ShadowBalance extends Metable, Payable
{
    /**
     * Create new or give an existing shadow balance.
     * If $uuid is null, it will create a new shadow balance.
     */
    public static function of(?string $uuid = null, array $meta = []): self;
}
