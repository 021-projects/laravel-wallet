<?php

namespace O21\LaravelWallet;

use Illuminate\Support\ServiceProvider as Provider;

class ServiceProvider extends Provider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function offerPublishing()
    {
    }
}
