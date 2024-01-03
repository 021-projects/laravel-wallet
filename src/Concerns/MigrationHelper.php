<?php

namespace O21\LaravelWallet\Concerns;

trait MigrationHelper
{
    protected function assertTableName(?string $key = null)
    {
        $tableNames = config('wallet.table_names');
        if (empty($tableNames)) {
            throw new \Exception('Error: config/wallet.php not loaded. Run [php artisan config:clear] and try again.');
        }

        if ($key && ! array_key_exists($key, $tableNames)) {
            return $key;
        }

        return $key ? $tableNames[$key] : $tableNames;
    }
}
