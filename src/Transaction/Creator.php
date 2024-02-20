<?php

namespace O21\LaravelWallet\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use O21\LaravelWallet\Contracts\Payable;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Contracts\TransactionPreparer;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Exception\FromOrOverchargeRequired;
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
        $this->transaction = app(Transaction::class);

        $this->currency(config('wallet.default_currency'));
    }

    public function commit(): Transaction
    {
        $create = function () {
            $before = $this->_before;
            $after = $this->_after;
            $tx = $this->transaction;

            /** @var \O21\LaravelWallet\Transaction\Preparer $preparer */
            $preparer = app(TransactionPreparer::class);
            $preparer->prepare($tx);

            if (is_callable($before)) {
                $before($tx);
            }

            if (! $this->allowOvercharge) {
                throw_if(! $tx->from, FromOrOverchargeRequired::class);

                $tx->from?->assertHaveFunds(
                    $tx->amount,
                    $tx->currency
                );
            }

            $tx->save();

            if (is_callable($after)) {
                $after($tx);
            }

            return $tx;
        };

        $safelyTransaction = new SafelyTransaction($create, $this->getLockRecord());

        return $safelyTransaction->setThrow(true)->run();
    }

    public function amount(string|float|int|Numeric $amount): self
    {
        $this->transaction->amount = num($amount)->positive();

        return $this;
    }

    public function currency(string $currency): self
    {
        $this->transaction->currency = $currency;

        return $this;
    }

    public function commission(string|float|int|Numeric $commission): self
    {
        $this->transaction->commission = num($commission)->positive();

        return $this;
    }

    public function hidden(): self
    {
        $this->transaction->hidden = true;

        return $this;
    }

    public function status(string $status): self
    {
        $this->transaction->status = $status;

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
            default => TransactionStatus::PENDING,
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

    public function to(Payable $payable): self
    {
        $this->transaction->to()->associate($payable);

        return $this;
    }

    public function from(Payable $payable): self
    {
        $this->transaction->from()->associate($payable);

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
        $tx = $this->transaction;
        $default = $tx->from?->balance($tx->currency) ?? $tx->to?->balance($tx->currency);

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
