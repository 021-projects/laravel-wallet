<?php

namespace O21\LaravelWallet\Contracts;

interface CurrencyConverterContract
{
    /**
     * @param  float  $amount
     * @param  string  $from From which currency convert
     * @param  string  $to  To which currency convert
     * @param  array  $data Transaction data
     * @return float
     */
    public function convert(
        float $amount,
        string $from,
        string $to,
        array $data = []
    ): float;
}
