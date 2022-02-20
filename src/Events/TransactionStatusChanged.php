<?php

namespace O21\LaravelWallet\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use O21\LaravelWallet\Contracts\TransactionContract;

class TransactionStatusChanged
{
    public const TYPE_COMPLETED  = 0x01;
    public const TYPE_PROCESSING = 0x02;
    public const TYPE_REJECTED   = 0x03;
    public const TYPE_FROZEN     = 0x04;

    /**
     * Type of event.
     *
     * @var int
     */
    protected int $type = 0;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public TransactionContract $transaction
    ) {
        $this->type = $this->resolveType();
    }

    protected function resolveType(): int
    {
        $transaction = $this->transaction;
        $originalStatus = $transaction->getOriginal('status');

        if ($originalStatus !== TransactionContract::STATUS_COMPLETED && $transaction->isCompleted()) {
            return self::TYPE_COMPLETED;
        }

        if ($originalStatus !== TransactionContract::STATUS_PROCESSING && $transaction->isProcessing()) {
            return self::TYPE_PROCESSING;
        }

        if ($originalStatus !== TransactionContract::STATUS_REJECTED && $transaction->isRejected()) {
            return self::TYPE_REJECTED;
        }

        if ($originalStatus !== TransactionContract::STATUS_FROZEN && $transaction->isFrozen()) {
            return self::TYPE_FROZEN;
        }

        return 0;
    }

    /**
     * @return \O21\LaravelWallet\Contracts\TransactionContract
     */
    public function getTransaction(): TransactionContract
    {
        return $this->transaction;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}
