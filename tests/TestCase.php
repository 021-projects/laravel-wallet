<?php

namespace O21\LaravelWallet\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use O21\LaravelWallet\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

use function Orchestra\Testbench\workbench_path;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(workbench_path('database/migrations'));
        $this->loadPackageMigrations();
    }

    protected function loadPackageMigrations()
    {
        $path = realpath(__DIR__.'/../database/migrations');
        $migrationFiles = scandir($path);
        foreach ($migrationFiles as $migrationFile) {
            if (in_array($migrationFile, ['.', '..'])) {
                continue;
            }
            $migration = require $path.'/'.$migrationFile;
            $migration->up();
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}
