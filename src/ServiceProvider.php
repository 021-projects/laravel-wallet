<?php

namespace O21\LaravelWallet;

use Illuminate\Support\ServiceProvider as Provider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class ServiceProvider extends Provider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->offerPublishing();

        $this->registerModelBindings();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/wallet.php',
            'wallet'
        );
    }

    protected function offerPublishing()
    {
        if (! function_exists('config_path')) {
            // function not available and 'publish' not relevant in Lumen
            return;
        }

        $this->publishes([
            __DIR__.'/../config/wallet.php' => config_path('wallet.php'),
        ], 'wallet-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_wallet_balances_table.php.stub' => $this->getMigrationFileName('create_wallet_balances_table.php'),
            __DIR__.'/../database/migrations/create_wallet_transactions_table.php.stub' => $this->getMigrationFileName('create_wallet_transactions_table.php'),
        ], 'wallet-migrations');
    }

    protected function registerModelBindings()
    {
        $config = $this->app->config['wallet.models'];
        if (! $config) {
            return;
        }

        $this->app->bind(WalletBalanceContract::class, $config['balance']);
        $this->app->bind(WalletTransactionContract::class, $config['transaction']);
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

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $migrationFileName) {
                return $filesystem->glob($path.'*_'.$migrationFileName);
            })
            ->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}
