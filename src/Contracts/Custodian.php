<?php

namespace O21\LaravelWallet\Contracts;

/**
 * @mixin \O21\LaravelWallet\Models\Custodian
 */
interface Custodian extends Metable, Payable
{
    /**
     * Create new or give an existing shadow balance.
     * If name is null, it will create a new shadow balance.
     *
     * If meta is not empty, it will update the meta of the shadow balance including exists.
     */
    public static function of(?string $name = null, array $meta = []): self;
}
