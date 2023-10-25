<?php

namespace O21\LaravelWallet\Models\Concerns;

use Illuminate\Support\Arr;

trait HasMetaColumn
{
    public function getMeta(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->meta;
        }

        return Arr::get($this->meta, $key, $default);
    }

    public function setMeta(
        array|string $key,
        float|array|int|string $value = null
    ): void {
        $meta = $this->meta;

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Arr::set($meta, $k, $v);
            }
        } else {
            Arr::set($meta, $key, $value);
        }

        $this->meta = $meta;
    }

    public function updateMeta(
        array|string $key,
        float|array|int|string $value = null
    ): bool {
        $this->setMeta($key, $value);

        return $this->save();
    }
}
