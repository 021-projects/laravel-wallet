<?php

namespace O21\LaravelWallet\Tests;

use Illuminate\Support\Facades\Event;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Events\TransactionCreated;
use O21\LaravelWallet\Events\TransactionDeleted;
use O21\LaravelWallet\Events\TransactionStatusChanged;
use O21\LaravelWallet\Events\TransactionUpdated;
use Workbench\App\Models\User;

class TransactionObserverTest extends TestCase
{
    public function test_events_dispatched(): void
    {
        Event::fake([
            TransactionCreated::class,
            TransactionStatusChanged::class,
            TransactionUpdated::class,
            TransactionDeleted::class,
        ]);

        $user = $this->newUser();

        $tx = deposit(100, 'USD')->to($user)->overcharge()->commit();

        Event::assertDispatched(TransactionCreated::class);

        $tx->updateStatus(TransactionStatus::PENDING);

        Event::assertDispatched(TransactionStatusChanged::class);
        Event::assertDispatched(TransactionUpdated::class);

        $tx->delete();

        Event::assertDispatched(TransactionDeleted::class);
    }

    protected function newUser(): User
    {
        return User::factory()->create();
    }
}
