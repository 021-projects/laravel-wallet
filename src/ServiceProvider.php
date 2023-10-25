<?php

namespace O21\LaravelWallet;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as Provider;
use Illuminate\Filesystem\Filesystem;
use O21\LaravelWallet\Commands\Make\TransactionProcessorCommand;
use O21\LaravelWallet\Commands\Rebuild\BalancesCommand;
use O21\LaravelWallet\Contracts\Balance;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Contracts\TransactionPreparer;
use O21\LaravelWallet\Listeners\TransactionEventsSubscriber;
use O21\LaravelWallet\Observers\TransactionObserver;
use O21\LaravelWallet\Transaction\Creator;
use O21\LaravelWallet\Transaction\Preparer;
use O21\LaravelWallet\Transaction\TransferCreator;

class ServiceProvider extends Provider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->offerPublishing();

        $this->registerModelBindings();

        $this->registerTransactionManipulators();

        $this->registerObservers();

        $this->registerCommands();

        $this->registerSubscribers();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/wallet.php',
            'wallet'
        );
    }

    protected function offerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/wallet.php' => config_path('wallet.php'),
        ], 'wallet-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_wallet_balances_table.php.stub' => $this->getMigrationFileName('create_wallet_balances_table.php'),
            __DIR__.'/../database/migrations/create_wallet_transactions_table.php.stub' => $this->getMigrationFileName('create_wallet_transactions_table.php'),
        ], 'wallet-migrations');
    }

    protected function registerTransactionManipulators(): void
    {
        $this->app->bind(TransactionPreparer::class, function () {
            return new Preparer();
        });

        $this->app->bind(TransactionCreator::class, function () {
            return new Creator();
        });

        $this->app->bind(TransferCreator::class, function () {
            return new TransferCreator();
        });
    }

    protected function registerModelBindings(): void
    {
        $config = $this->app->config['wallet.models'];
        if (! $config) {
            return;
        }

        $this->app->bind(Balance::class, $config['balance']);
        $this->app->bind(Transaction::class, $config['transaction']);
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @return string
     */
    protected function getMigrationFileName($migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return collect($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $migrationFileName) {
                return $filesystem->glob($path.'*_'.$migrationFileName);
            })
            ->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }

    protected function registerObservers(): void
    {
        $this->app->bind(
            TransactionObserver::class,
            TransactionObserver::class
        );

        $transactionClass = $this->app->make(Transaction::class);

        $transactionClass::observe($this->app->make(TransactionObserver::class));
    }

    protected function registerCommands(): void
    {
        $this->commands([
            BalancesCommand::class,
            TransactionProcessorCommand::class
        ]);
    }

    protected function registerSubscribers(): void
    {
        Event::subscribe(TransactionEventsSubscriber::class);
    }
}
