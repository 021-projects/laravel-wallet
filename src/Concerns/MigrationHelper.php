<?php

namespace O21\LaravelWallet\Concerns;

trait MigrationHelper
{
    protected function assertTableNames(?string $key = null)
    {
        $tableNames = config('wallet.table_names');
        if (empty($tableNames)) {
            throw new \Exception('Error: config/wallet.php not loaded. Run [php artisan config:clear] and try again.');
        }

        return $key ? $tableNames[$key] : $tableNames;
    }
}
