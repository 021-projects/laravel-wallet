<?php

namespace O21\LaravelWallet\TransactionHandlers;

class WriteOffHandler extends AbstractHandler
{
    public function validAmount(): string
    {
        return crypto_number(-abs($this->transaction->amount));
    }
}
