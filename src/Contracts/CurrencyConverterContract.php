<?php

namespace O21\LaravelWallet\Contracts;

interface CurrencyConverterContract
{
    /**
     * @param  string  $amount
     * @param  string  $from From which currency convert
     * @param  string  $to  To which currency convert
     * @param  array  $data Transaction data
     * @return string
     */
    public function convert(
        string $amount,
        string $from,
        string $to,
        array $data = []
    ): string;
}
