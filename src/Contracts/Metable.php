<?php

namespace O21\LaravelWallet\Contracts;

interface Metable
{
    public function getMeta(?string $key = null, $default = null);

    public function setMeta(
        array|string $key,
        float|array|int|string|null $value = null
    ): void;

    public function updateMeta(
        array|string $key,
        float|array|int|string|null $value = null
    ): bool;
}
