<?php

namespace O21\LaravelWallet\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class TransactionCreated extends TransactionEvent implements ShouldDispatchAfterCommit
{
}
