<?php

if (! function_exists('wallet_handler')) {

    /**
     * Gets wallet content handler object.
     *
     * @param string $id
     * @param bool $returnClass
     * @param mixed ...$arguments
     * @return mixed|object|null
     */
    function wallet_handler(string $id, bool $returnClass = false, ...$arguments)
    {
        $handlers = config('wallet.handlers');

        $class = $handlers[$id] ?? null;

        if (! ($class && class_exists($class)) || $returnClass) {
            return $class;
        }

        return app($class);
    }
}

if (! function_exists('wallet_handler_id')) {

    /**
     * Gets wallet handler id by class.
     *
     * @param $class
     * @return false|int|string
     */
    function wallet_handler_id($class)
    {
        return array_search($class, config('wallet.handlers'), true);
    }
}

if (! function_exists('crypto_number')) {

    /**
     * Format bitcoin number.
     *
     * @param $value
     * @return string
     */
    function crypto_number($value)
    {
        return number_format_trim_trailing_zero($value, 8, '.', '');
    }
}

if (! function_exists('number_format_trim_trailing_zero')) {

    /**
     * Formats a number and removes trailing zeros.
     *
     * @return string
     */
    function number_format_trim_trailing_zero()
    {
        return trim_trailing_zero(number_format(...func_get_args()));
    }
}

if (! function_exists('trim_trailing_zero')) {

    /**
     * Removes trailing zeros.
     *
     * @param $number
     * @return string
     */
    function trim_trailing_zero($number)
    {
        return str_contains($number, '.')
            ? rtrim(rtrim($number,'0'),'.')
            : $number;
    }
}
