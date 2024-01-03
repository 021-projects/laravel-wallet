<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->setUpConfig();
    }

    protected function setUpDatabase(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        include_once __DIR__.'/../database/migrations/create_balances_table.php.stub';
        include_once __DIR__.'/../database/migrations/create_transactions_table.php.stub';

        (new \CreateBalancesTable())->up();
        (new \CreateTransactionsTable())->up();
    }

    protected function setUpConfig(): void
    {
        config([
            'wallet.models.user' => \Tests\Models\User::class,
        ]);
    }
}
