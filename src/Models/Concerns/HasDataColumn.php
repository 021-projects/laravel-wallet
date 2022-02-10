<?php

namespace O21\LaravelWallet\Models\Concerns;

use Illuminate\Support\Arr;

trait HasDataColumn
{
    /**
     * @param  array|string  $key
     * @param  array|string|int|float|null  $value
     * @return bool
     */
    public function updateDataColumn($key, $value = null): bool
    {
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
