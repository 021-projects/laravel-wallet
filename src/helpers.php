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
