<?php

namespace O21\LaravelWallet\Models\Concerns;

use Illuminate\Support\Arr;

trait HasDataColumn
{
    public function updateDataColumn(
        array|string $key,
        float|array|int|string $value = null
    ): bool {
        $data = $this->data;

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Arr::set($data, $k, $v);
            }
        } else {
            Arr::set($data, $key, $value);
        }

        $this->data = $data;

        return $this->save();
    }
}
