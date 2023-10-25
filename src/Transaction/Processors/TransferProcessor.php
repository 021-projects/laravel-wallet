<?php

namespace O21\LaravelWallet\Transaction\Processors;

use O21\LaravelWallet\Concerns\ModelStubs;
use O21\LaravelWallet\Contracts\SupportsBalance;
use O21\LaravelWallet\Contracts\TransactionProcessor;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Transaction\Processors\Concerns\BaseProcessor;
use O21\LaravelWallet\Transaction\Processors\Concerns\InitialSuccess;

class TransferProcessor implements TransactionProcessor
{
    use ModelStubs;
    use BaseProcessor;
    use InitialSuccess;

    public function creating(): void
    {
        if (! $this->getReceiver()) {
            $this->transaction->status = TransactionStatus::FAILED;
            return;
        }

        $this->transaction->status = TransactionStatus::SUCCESS;
    }

    public function created(): void
    {
        if (! $this->transaction->hasStatus(TransactionStatus::SUCCESS)) {
            return;
        }

        $this->sendFundsToReceiver();
    }

    public function statusChanged(string $status, string $oldStatus): void
    {
        if ($status === TransactionStatus::SUCCESS->value && $oldStatus !== $status) {
            $this->sendFundsToReceiver();
        }

        if ($oldStatus === TransactionStatus::SUCCESS->value && $oldStatus !== $status) {
            $this->cancelSendingFunds();
        }
    }

    protected function sendFundsToReceiver(): void
    {
        if ($this->isFundsAlreadySent()) {
            return;
        }

        // It's send funds transaction
        if ($this->getReceiverId() === $this->transaction->user_id) {
            return;
        }

        $transaction = transfer($this->transaction->amount, $this->transaction->currency)
            ->receiveFunds($this->getReceiver())
            ->meta([
                'parentTransactionId' => $this->transaction->id,
                ...$this->transaction->meta,
            ])
            ->commit();

        $this->transaction->updateMeta([
            'sendFundsTransactionId' => $transaction->id,
        ]);
    }

    protected function cancelSendingFunds(): void
    {
        $sendFundsTransactionId = $this->transaction->getMeta('sendFundsTransactionId');
        if (! $sendFundsTransactionId) {
            return;
        }

        $sendFundsTransaction = $this->findTransaction($sendFundsTransactionId);
        if (! $sendFundsTransaction) {
            return;
        }

        $sendFundsTransaction->updateStatus(TransactionStatus::CANCELED);
    }

    public function prepareAmount(string $amount): string
    {
        if ($this->getReceiverId() === $this->transaction->user_id) {
            return num($amount)->positive();
        }

        return num($amount)->negative();
    }

    protected function isFundsAlreadySent(): bool
    {
        return (bool)$this->transaction->getMeta('sendFundsTransactionId');
    }

    protected function getReceiver(): ?SupportsBalance
    {
        return $this->findUser($this->getReceiverId());
    }

    protected function getReceiverId(): int
    {
        return (int)$this->transaction->getMeta('receiverId');
    }

    protected function getSender(): ?SupportsBalance
    {
        return $this->findUser($this->getSenderId());
    }

    protected function getSenderId(): int
    {
        return (int)$this->transaction->getMeta('senderId');
    }
}
