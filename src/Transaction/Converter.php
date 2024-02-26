<?php

namespace O21\LaravelWallet\Transaction;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use O21\LaravelWallet\Concerns\Batchable;
use O21\LaravelWallet\Concerns\Eventable;
use O21\LaravelWallet\Concerns\Lockable;
use O21\LaravelWallet\Concerns\Overchargable;
use O21\LaravelWallet\Contracts\Converter as IConverter;
use O21\LaravelWallet\Contracts\Payable;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Numeric;
use O21\SafelyTransaction;

use function O21\LaravelWallet\ConfigHelpers\default_currency;
use function O21\LaravelWallet\ConfigHelpers\tx_currency_scaling;

class Converter implements IConverter
{
    use Batchable, Eventable, Lockable, Overchargable;

    protected ?string $srcCurrency = null;

    protected ?string $destCurrency = null;

    protected Numeric $conversionAmount;

    protected Numeric $srcCommission;

    protected Numeric $destCommission;

    protected Numeric $rateMultiplier;

    protected array $meta = [];

    protected ?TransactionCreator $debitTxCreator = null;

    protected ?TransactionCreator $creditTxCreator = null;

    public function __construct()
    {
        $this->conversionAmount = num(0);
        $this->srcCurrency = default_currency();
        $this->srcCommission = num(0);
        $this->destCommission = num(0);
        $this->rateMultiplier = num(0);

        $this->on('amount:changed', $this->onAmountChanged(...));
        $this->on('commission:changed', $this->onCommissionChanged(...));
        $this->on('rate:changed', $this->onRateChanged(...));
    }

    public function performOn(Payable $payable): Collection
    {
        $perform = function () use ($payable) {
            $this->validate();

            $debitTxCreator = $this->makeDebitTx($payable);
            $creditTxCreator = $this->makeCreditTx($payable);

            $this->fire('before', [
                'converter' => $this,
                'debitTxCreator' => $debitTxCreator,
                'creditTxCreator' => $creditTxCreator,
            ]);

            $this->setBatch();

            $debitTx = $debitTxCreator->commit();
            $creditTx = $creditTxCreator->commit();

            $this->creditTxCreator = null;
            $this->debitTxCreator = null;

            $this->fire('after', [
                'converter' => $this,
                'debitTx' => $debitTx,
                'creditTx' => $creditTx,
            ]);

            return collect([
                'debit' => $debitTx,
                'credit' => $creditTx,
            ]);
        };

        $lockRecord = $this->lockRecord ?: $payable->balance($this->srcCurrency);

        $safelyTransaction = new SafelyTransaction($perform, $lockRecord);

        return $safelyTransaction->setThrow(true)->run();
    }

    protected function setBatch(): void
    {
        $batch = $this->nextBatch();
        $this->validateBatch($batch);
        $this->debitTxCreator->batch($batch, exists: true);
        $this->creditTxCreator->batch($batch, exists: true);
    }

    protected function makeDebitTx(Payable $payable): TransactionCreator
    {
        if (! $this->debitTxCreator) {
            $this->debitTxCreator = tx($this->debitAmount(), $this->srcCurrency)
                ->commission($this->srcCommission)
                ->processor($this->debitProcessor())
                ->meta($this->getMeta())
                ->from($payable)
                ->overcharge($this->allowOvercharge)
                ->lockOnRecord(false);
        }

        return $this->debitTxCreator;
    }

    protected function makeCreditTx(Payable $payable): TransactionCreator
    {
        if (! $this->creditTxCreator) {
            $this->creditTxCreator = tx($this->creditAmount(), $this->destCurrency)
                ->commission($this->destCommission)
                ->processor($this->creditProcessor())
                ->meta($this->getMeta())
                ->to($payable)
                ->overcharge()
                ->lockOnRecord(false);
        }

        return $this->creditTxCreator;
    }

    protected function creditAmount(): Numeric
    {
        return num($this->conversionAmount)
            ->sub($this->srcCommission)
            ->mul($this->rateMultiplier)
            ->scale(tx_currency_scaling($this->destCurrency));
    }

    protected function debitAmount(): Numeric
    {
        return num($this->conversionAmount)
            ->scale(tx_currency_scaling($this->srcCurrency));
    }

    protected function creditProcessor(): string
    {
        return 'conversion_credit';
    }

    protected function debitProcessor(): string
    {
        return 'conversion_debit';
    }

    public function at(float|Numeric|int|string $rate): IConverter
    {
        $this->rateMultiplier = num($rate);

        $this->fire('rate:changed');

        return $this;
    }

    protected function onRateChanged(): void
    {
        if (! $this->duringConversion()) {
            return;
        }

        $this->debitTxCreator->amount($this->debitAmount());
        $this->creditTxCreator->amount($this->creditAmount());
    }

    public function amount(float|Numeric|int|string $amount): IConverter
    {
        $this->conversionAmount = num($amount);

        $this->fire('amount:changed');

        return $this;
    }

    protected function onAmountChanged(): void
    {
        if (! $this->duringConversion()) {
            return;
        }

        $this->debitTxCreator->amount($this->debitAmount());
        $this->creditTxCreator->amount($this->creditAmount());
    }

    public function commission(
        float|Numeric|int|string|null $src = null,
        float|Numeric|int|string|null $dest = null
    ): IConverter {
        throw_if(
            $src === null && $dest === null,
            new InvalidArgumentException('At least one commission value must be provided')
        );

        if ($src !== null) {
            $this->srcCommission = num($src);
        }
        if ($dest !== null) {
            $this->destCommission = num($dest);
        }

        $this->fire('commission:changed');

        return $this;
    }

    protected function onCommissionChanged(): void
    {
        if (! $this->duringConversion()) {
            return;
        }

        $this->debitTxCreator->commission($this->srcCommission);
        $this->creditTxCreator->commission($this->destCommission);
    }

    public function before(callable $callback): IConverter
    {
        $this->off('before');
        $this->on('before', $callback);

        return $this;
    }

    public function after(callable $callback): IConverter
    {
        $this->off('after');
        $this->on('after', $callback);

        return $this;
    }

    public function from(string $currency): IConverter
    {
        $this->srcCurrency = $currency;

        return $this;
    }

    public function to(string $currency): IConverter
    {
        $this->destCurrency = $currency;

        return $this;
    }

    public function meta(array $meta): IConverter
    {
        $this->meta = $meta;

        return $this;
    }

    protected function getMeta(): array
    {
        return array_merge($this->meta, [
            'rate' => $this->rateMultiplier->get(),
        ]);
    }

    protected function validate(): void
    {
        throw_if(
            $this->conversionAmount->lessThanOrEqual(0),
            InvalidArgumentException::class,
            'Amount must be greater than 0'
        );

        throw_if(
            $this->srcCurrency === null || $this->destCurrency === null,
            InvalidArgumentException::class,
            'Source and destination currencies must be set'
        );

        throw_if(
            $this->rateMultiplier->lessThanOrEqual(0),
            InvalidArgumentException::class,
            'Rate must be greater than 0'
        );

        throw_if(
            $this->srcCurrency === $this->destCurrency,
            InvalidArgumentException::class,
            'Source and destination currencies must be different'
        );
    }

    protected function duringConversion(): bool
    {
        return $this->debitTxCreator !== null && $this->creditTxCreator !== null;
    }
}
