<?php

namespace O21\LaravelWallet;

/**
 * Class for safe numeric operations
 */
class Numeric
{
    protected string $_dirtyValue;

    protected int $_scale;

    public function __construct(
        string|float|int|Numeric $value,
        ?int $scale = null
    ) {
        $scale ??= config('wallet.balance.max_scale') ?? 8;
        $this->_dirtyValue = $value instanceof self
            ? (string)$value
            : $this->format(
                decimals: $scale,
                value: $value
            );
        $this->_scale = $scale;
    }

    public function __toString(): string
    {
        return $this->trimTrailingZero($this->format($this->_scale));
    }

    public function get(): string
    {
        return $this->__toString();
    }

    public function positive(): string
    {
        return $this->trimTrailingZero($this->format(
            decimals: $this->_scale,
            value: abs($this->_dirtyValue)
        ));
    }

    public function negative(): string
    {
        return $this->trimTrailingZero($this->format(
            decimals: $this->_scale,
            value: -abs($this->_dirtyValue)
        ));
    }

    public function add(string|float|int|Numeric $value): self
    {
        $this->_dirtyValue = bcadd((string)$this, (string)(new self($value)), $this->_scale);
        return $this;
    }

    public function sub(string|float|int|Numeric $value): self
    {
        $this->_dirtyValue = bcsub((string)$this, (string)(new self($value)), $this->_scale);
        return $this;
    }

    public function mul(string|float|int|Numeric $value): self
    {
        $this->_dirtyValue = bcmul((string)$this, (string)(new self($value)), $this->_scale);
        return $this;
    }

    public function div(string|float|int|Numeric $value): self
    {
        $this->_dirtyValue = bcdiv((string)$this, (string)(new self($value)), $this->_scale);
        return $this;
    }

    public function equals(string|float|int|Numeric $value): bool
    {
        return bccomp((string)$this, (string)(new self($value)), $this->_scale) === 0;
    }

    public function greaterThan(string|float|int|Numeric $value): bool
    {
        return bccomp((string)$this, (string)(new self($value)), $this->_scale) === 1;
    }

    public function lessThan(string|float|int|Numeric $value): bool
    {
        return bccomp((string)$this, (string)(new self($value)), $this->_scale) === -1;
    }

    public function greaterThanOrEqual(string|float|int|Numeric $value): bool
    {
        return bccomp((string)$this, (string)(new self($value)), $this->_scale) >= 0;
    }

    public function lessThanOrEqual(string|float|int|Numeric $value): bool
    {
        return bccomp((string)$this, (string)(new self($value)), $this->_scale) <= 0;
    }

    /**
     * Get the minimum value of the given values
     *
     * @param  string|float|int|Numeric[]  ...$values
     * @return \O21\LaravelWallet\Numeric
     */
    public function min(...$values): Numeric
    {
        $min = $this;
        foreach ($values as $value) {
            if (num($value)->lessThan($min)) {
                $min = $value;
            }
        }
        return new self($min);
    }

    /**
     * Get the maximum value of the given values
     *
     * @param  string|float|int|Numeric[]  ...$values
     * @return \O21\LaravelWallet\Numeric
     */
    public function max(...$values): Numeric
    {
        $max = $this;
        foreach ($values as $value) {
            if (num($value)->greaterThan($max)) {
                $max = $value;
            }
        }
        return new self($max);
    }

    public function format(
        int $decimals = 8,
        string $decimalSeparator = '.',
        string $thousandsSeparator = '',
        mixed $value = null
    ): string {
        $value ??= $this->_dirtyValue;
        return number_format(
            (float)$value,
            $decimals,
            $decimalSeparator,
            $thousandsSeparator
        );
    }

    protected function trimTrailingZero(string $value): string
    {
        return str_contains($value, '.')
            ? rtrim(rtrim($value, '0'), '.')
            : $value;
    }

    public function scale(int $scale): self
    {
        $this->_scale = $scale;
        return $this;
    }
}
