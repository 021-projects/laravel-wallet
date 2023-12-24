<?php

namespace O21\LaravelWallet\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Contracts\SupportsBalance;
use O21\LaravelWallet\Contracts\TransactionPreparer;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Numeric;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialHolding;
use O21\LaravelWallet\Transaction\Processors\Contracts\InitialSuccess;
use O21\SafelyTransaction;

class Creator implements TransactionCreator
{
    protected Transaction $transaction;

    protected Model|Builder|bool|null $lockRecord = null;
    /**
     * @var callable|null
     */
    protected $_before = null;
    /**
     * @var callable|null
     */
    protected $_after = null;
    protected bool $allowOvercharge = false;

    public function __construct()
    {
        $txClass = app(Transaction::class);
        $this->transaction = new $txClass();

        $this->currency(config('wallet.default_currency'));
    }

    public function commit(): Transaction
    {
        $create = function () {
            $before = $this->_before;
            $after = $this->_after;
            $transaction = $this->transaction;

            /** @var \O21\LaravelWallet\Transaction\Preparer $preparer */
            $preparer = app(TransactionPreparer::class);
            $preparer->prepare($transaction);

            if (is_callable($before)) {
                $before($transaction);
            }

            if (! $this->allowOvercharge && num($transaction->amount)->lessThan(0)) {
                $transaction->User->assertHaveFunds(
                    $transaction->total,
                    $transaction->currency
                );
            }

            $transaction->save();

            if (is_callable($after)) {
                $after($transaction);
            }

            return $transaction;
        };

        $safelyTransaction = new SafelyTransaction($create, $this->getLockRecord());
        return $safelyTransaction->setThrow(true)->run();
    }

    public function amount(string|float|int|Numeric $amount): self
    {
        $this->transaction->amount = (string)num($amount);
        return $this;
    }

    public function currency(string $currency): self
    {
        $this->transaction->currency = $currency;
        return $this;
    }

    public function commission(string|float|int|Numeric $commission): self
    {
        $this->transaction->commission = num($commission)->negative();
        return $this;
    }

    public function status(TransactionStatus|string $status): self
    {
        $this->transaction->status = $status instanceof TransactionStatus
            ? $status->value
            : $status;

        return $this;
    }

    public function setDefaultStatus(): self
    {
        if (! ($processor = $this->transaction->processor)) {
            return $this;
        }

        $initialSuccess = $processor instanceof InitialSuccess;
        $initialHolding = $processor instanceof InitialHolding;

        $this->status(match (true) {
            $initialHolding => TransactionStatus::ON_HOLD,
            $initialSuccess => TransactionStatus::SUCCESS,
            default         => TransactionStatus::PENDING,
        });

        return $this;
    }

    public function processor(string $processor): self
    {
        if (class_exists($processor)) {
            $this->transaction->processor_id = array_search(
                $processor,
                config('wallet.processors'),
                true
            );
            return $this;
        }

        if (! array_key_exists($processor, config('wallet.processors'))) {
            throw new \RuntimeException('Error: unknown transaction processor');
        }

        $this->transaction->setProcessor($processor);

        $this->setDefaultStatus();

        return $this;
    }

    public function to(SupportsBalance $user): TransactionCreator
    {
        return $this->user($user);
    }

    public function from(SupportsBalance $user): TransactionCreator
    {
        return $this->user($user);
    }

    public function user(SupportsBalance $user): self
    {
        $this->transaction->User()->associate($user);
        return $this;
    }

    public function meta(array $meta): self
    {
        $this->transaction->setMeta($meta);
        return $this;
    }

    public function lockOnRecord(Model|Builder|bool $lockRecord): self
    {
        $this->lockRecord = $lockRecord;
        return $this;
    }

    protected function getLockRecord(): Model|Builder|null
    {
        $default = $this->transaction->User->getBalance(
            $this->transaction->currency
        );

        if (is_bool($this->lockRecord)) {
            return $this->lockRecord ? $default : null;
        }

        return $this->lockRecord ?? $default;
    }

    public function before(callable $before): self
    {
        $this->_before = $before;
        return $this;
    }

    public function after(callable $after): self
    {
        $this->_after = $after;
        return $this;
    }

    public function overcharge(bool $allow = true): self
    {
        $this->allowOvercharge = $allow;
        return $this;
    }
}
