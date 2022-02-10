<?php

namespace O21\LaravelWallet\Contracts;

/**
 * @property int $id
 * @extends \Illuminate\Database\Eloquent\Model
 */
interface UserContract
{
    public function getBalance(string $currency): BalanceContract;
}
