<?php

namespace O21\LaravelWallet\TransactionHandlers;

class WriteOffHandler extends AbstractHandler
{
    public function validAmount(): float
    {
        return -abs($this->transaction->amount);
    }
}
