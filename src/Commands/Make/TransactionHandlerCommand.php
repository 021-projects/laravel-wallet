<?php

namespace O21\LaravelWallet\Commands\Make;

use Illuminate\Console\GeneratorCommand;

class TransactionHandlerCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = '021-wallet-make:transaction-handler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new transaction handler';

    protected $type = 'Transaction handler';

    protected function getStub(): string
    {
        $stub = '/stubs/transaction-handler.stub';

        return __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\TransactionHandlers';
    }
}
