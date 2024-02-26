<?php

namespace O21\LaravelWallet;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as Provider;
use O21\LaravelWallet\Commands\Make\TransactionProcessorCommand;
use O21\LaravelWallet\Commands\Rebuild\BalancesCommand;
use O21\LaravelWallet\Commands\Rebuild\TxBalanceStatesCommand;
use O21\LaravelWallet\Contracts\Balance;
use O21\LaravelWallet\Contracts\BalanceState;
use O21\LaravelWallet\Contracts\Converter as IConverter;
use O21\LaravelWallet\Contracts\Custodian;
use O21\LaravelWallet\Contracts\Transaction;
use O21\LaravelWallet\Contracts\TransactionCreator;
use O21\LaravelWallet\Enums\TransactionStatus;
use O21\LaravelWallet\Listeners\TransactionEventsSubscriber;
use O21\LaravelWallet\Observers\TransactionObserver;
use O21\LaravelWallet\Transaction\Converter;
use O21\LaravelWallet\Transaction\Creator;

use function O21\LaravelWallet\ConfigHelpers\get_model_class;
use function O21\LaravelWallet\ConfigHelpers\tx_accounting_statuses;

class ServiceProvider extends Provider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->offerPublishing();

        $this->registerModelBindings();

        $this->registerTransactionManipulators();

        $this->registerTransactionAccountingStatuses();

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
            __DIR__.'/../database/migrations/create_balances_table.php.stub' => $this->getMigrationFileName(
                'create_balances_table.php'
            ),
            __DIR__.'/../database/migrations/create_transactions_table.php.stub' => $this->getMigrationFileName(
                'create_transactions_table.php'
            ),
            __DIR__.'/../database/migrations/create_balance_states_table.php.stub' => $this->getMigrationFileName(
                'create_balance_states_table.php',
                order: 1
            ),
            __DIR__.'/../database/migrations/create_custodians_table.php.stub' => $this->getMigrationFileName(
                'create_custodians_table.php',
                order: 2
            ),
        ], 'wallet-migrations');
    }

    protected function registerTransactionManipulators(): void
    {
        $this->app->bind(TransactionCreator::class, function () {
            return new Creator();
        });

        $this->app->bind(IConverter::class, function () {
            return new Converter();
        });
    }

    protected function registerTransactionAccountingStatuses(): void
    {
        TransactionStatus::accounting(tx_accounting_statuses());
    }

    protected function registerModelBindings(): void
    {
        $this->app->bind(
            Balance::class,
            get_model_class('balance') ?? Models\Balance::class
        );
        $this->app->bind(
            BalanceState::class,
            get_model_class('balance_state') ?? Models\BalanceState::class
        );
        $this->app->bind(
            Transaction::class,
            get_model_class('transaction') ?? Models\Transaction::class
        );
        $this->app->bind(
            Custodian::class,
            get_model_class('custodian') ?? Models\Custodian::class
        );
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     */
    protected function getMigrationFileName($migrationFileName, int $order = 0): string
    {
        $timestamp = date('Y_m_d_His', time() + $order);

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
            TxBalanceStatesCommand::class,
            TransactionProcessorCommand::class,
        ]);
    }

    protected function registerSubscribers(): void
    {
        Event::subscribe(TransactionEventsSubscriber::class);
    }
}
