<?php

namespace O21\LaravelWallet\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use O21\LaravelWallet\Concerns\Eventable;
use O21\LaravelWallet\Concerns\Lockable;
use O21\LaravelWallet\Concerns\Overchargable;
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
    use Eventable, Overchargable, Lockable;

    protected Transaction $tx;

    public function __construct()
    {
        $this->tx = app(Transaction::class);

        $this->currency(config('wallet.default_currency'));
    }

    public function commit(): Transaction
    {
        $create = function () {
            $tx = $this->tx;

            app(TransactionPreparer::class)->prepare($tx);

            $this->trigger('before', $tx);

            $this->assertHaveSender();

            if ($tx->from && ! $this->allowOvercharge) {
                $this->assertHaveFunds(
                    $tx->from,
                    $tx->amount,
                    $tx->currency
                );
            }

            $tx->save();

            $this->trigger('after', $tx);

            return $tx;
        };

        $safelyTransaction = new SafelyTransaction($create, $this->getLockRecord());

        return $safelyTransaction->setThrow(true)->run();
    }

    public function amount(string|float|int|Numeric $amount): self
    {
        $this->tx->amount = num($amount)->positive();

        return $this;
    }

    public function currency(string $currency): self
    {
        $this->tx->currency = $currency;

        return $this;
    }

    public function commission(string|float|int|Numeric $commission): self
    {
        $this->tx->commission = num($commission)->positive();

        return $this;
    }

    public function status(string $status): self
    {
        $this->tx->status = $status;

        return $this;
    }

    public function setDefaultStatus(): self
    {
        if (! ($processor = $this->tx->processor)) {
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
            $this->tx->processor_id = array_search(
                $processor,
                config('wallet.processors'),
                true
            );

            return $this;
        }

        if (! array_key_exists($processor, config('wallet.processors'))) {
            throw new \RuntimeException('Error: unknown transaction processor');
        }

        $this->tx->setProcessor($processor);

        $this->setDefaultStatus();

        return $this;
    }

    public function to(Payable $payable): self
    {
        $this->tx->to()->associate($payable);

        return $this;
    }

    public function from(Payable $payable): self
    {
        $this->tx->from()->associate($payable);

        return $this;
    }

    public function meta(array $meta): self
    {
        $this->tx->setMeta($meta);

        return $this;
    }

    protected function getLockRecord(): Model|Builder|null
    {
        $tx = $this->tx;
        $default = $tx->from?->balance($tx->currency) ?? $tx->to?->balance($tx->currency);

        if (is_bool($this->lockRecord)) {
            return $this->lockRecord ? $default : null;
        }

        return $this->lockRecord ?? $default;
    }

    public function before(callable $callback): self
    {
        $this->off('before');
        $this->on('before', $callback);

        return $this;
    }

    public function after(callable $callback): self
    {
        $this->off('after');
        $this->on('after', $callback);

        return $this;
    }

    public function overcharge(bool $allow = true): self
    {
        $this->allowOvercharge = $allow;

        return $this;
    }

    protected function assertHaveSender(): void
    {
        throw_if(
            ! $this->tx->from && ! $this->allowOvercharge,
            FromOrOverchargeRequired::class
        );
    }
}
