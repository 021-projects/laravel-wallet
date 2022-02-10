<?php

namespace O21\LaravelWallet\TransactionHandlers\Concerns;

trait BalanceManipulations
{
    protected function recalculateBalance(): bool
    {
        return $this->getTransaction()
            ->getUserBalance()
            ->recalculate();
    }
}
