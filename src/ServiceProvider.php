<?php

namespace O21\LaravelWallet;

use Illuminate\Support\ServiceProvider as Provider;
use Illuminate\Filesystem\Filesystem;
use O21\LaravelWallet\Contracts\BalanceContract;
use O21\LaravelWallet\Contracts\CurrencyConverterContract;
use O21\LaravelWallet\Contracts\TransactionContract;

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

        $this->registerConverter();

        $this->registerObservers();
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

    protected function registerModelBindings(): void
    {
        $config = $this->app->config['wallet.models'];
        if (! $config) {
            return;
        }

        $this->app->bind(BalanceContract::class, $config['balance']);
        $this->app->bind(TransactionContract::class, $config['transaction']);
    }

    protected function registerConverter(): void
    {
        $this->app->bind(CurrencyConverterContract::class, function ($app) {
            $config = $app->config['wallet.currencies'];

            if ($config['convert'] && class_exists($config['converter'])) {
                return new $config['converter']();
            }

            return null;
        });
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
        $config = $this->app->config['wallet.observers'];

        $transactionClass = $this->app->make(TransactionContract::class);

        $transactionClass::observe($config['transaction']);
    }
}
