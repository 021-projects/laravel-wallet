<?php

namespace O21\LaravelWallet\Concerns;

use O21\LaravelWallet\Contracts\Payable;
use O21\Numeric\Numeric;

trait Overchargable
{
    protected bool $allowOvercharge = false;

    public function overcharge(bool $allow = true): self
    {
        $this->allowOvercharge = $allow;

        return $this;
    }

    protected function assertHaveFunds(
        Payable $payable,
        float|int|string|Numeric $needs,
        ?string $currency = null
    ): void {
        if ($this->allowOvercharge) {
            return;
        }

        $payable->assertHaveFunds($needs, $currency);
    }
}
