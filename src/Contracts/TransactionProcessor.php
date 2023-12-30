<?php

namespace O21\LaravelWallet\Contracts;

/**
 * Interface TransactionProcessor
 * @package O21\LaravelWallet\Contracts
 *
 * @method void statusChanged(string $status, string $oldStatus) Called in jobs by TransactionStatusChanged event
 * @method void creating() Called before transaction is created
 * @method void created() Called in jobs by TransactionCreated event
 * @method void updating() Called before transaction is updated
 * @method void updated() Called in jobs by TransactionUpdated event
 * @method void deleting() Called before transaction is deleted
 * @method void deleted() Called in jobs by TransactionDeleted event
 *
 */
interface TransactionProcessor
{
    public function __construct(Transaction $transaction);

    /**
     * Method for preparing metadata
     *
     * @param  array  $meta
     * @return array
     */
    public function prepareMeta(array $meta): array;
}
