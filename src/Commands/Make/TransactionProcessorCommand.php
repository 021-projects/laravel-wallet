<?php

namespace O21\LaravelWallet\Commands\Make;

use Illuminate\Console\GeneratorCommand;

class TransactionProcessorCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:tx-processor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new transaction processor';

    protected $type = 'Transaction processor';

    protected function getStub(): string
    {
        $stub = '/stubs/TransactionProcessor.stub';

        return __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Transactions\Processors';
    }
}
