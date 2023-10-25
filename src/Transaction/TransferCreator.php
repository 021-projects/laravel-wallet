<?php

namespace O21\LaravelWallet\Transaction;

use O21\LaravelWallet\Contracts\SupportsBalance;
use O21\LaravelWallet\Contracts\TransactionCreator;

class TransferCreator extends Creator
{
    protected ?SupportsBalance $receiver = null;

    public function to(SupportsBalance $user): TransactionCreator
    {
        $this->receiver = $user;
        $this->meta([
            'receiverId' => $user->getAuthIdentifier(),
        ]);
        return $this;
    }

    public function user(SupportsBalance $user): Creator
    {
        $this->meta([
            'senderId' => $user->getAuthIdentifier(),
        ]);
        return parent::user($user);
    }

    public function receiveFunds(SupportsBalance $receiver): TransactionCreator
    {
        $this->transaction->user_id = $receiver->getAuthIdentifier();
        $this->meta([
            'receiverId' => $this->transaction->user_id,
        ]);
        return $this;
    }
}
