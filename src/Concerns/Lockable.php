<?php

namespace O21\LaravelWallet\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

trait Lockable
{
    protected Model|Builder|bool|null $lockRecord = null;

    public function lockOnRecord(Model|Builder|bool $lockRecord): self
    {
        $this->lockRecord = $lockRecord;

        return $this;
    }
}
