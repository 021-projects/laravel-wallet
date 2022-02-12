<?php

namespace O21\LaravelWallet\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class TrimZero implements CastsAttributes
{
    private const WITHOUT_DIGITS_REGEX = '#\.0*$/#';
    private const HAS_DIGITS_REGEX = '#(\..*?)0*$#';

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string
     */
    public function get($model, $key, $value, $attributes)
    {
        if (preg_match(self::WITHOUT_DIGITS_REGEX, $value)) {
            return preg_replace(self::WITHOUT_DIGITS_REGEX, '', $value);
        }

        return preg_replace(self::HAS_DIGITS_REGEX, '', $value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, $key, $value, $attributes)
    {
        return $value;
    }
}
