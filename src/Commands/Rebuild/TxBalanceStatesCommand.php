<?php

namespace O21\LaravelWallet\Commands\Rebuild;

use Illuminate\Console\Command;
use O21\LaravelWallet\Contracts\Transaction;

class TxBalanceStatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:rebuild-tx-balance-states';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreates balance states for each transaction';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $txModel = app(Transaction::class);

        $progress = $this->output->createProgressBar($txModel::count());
        $progress->start();

        $txModel::chunk(100, function ($txs) use ($progress) {
            $txs->each(function(Transaction $tx) use ($progress) {
                $this->rebuildStates($tx);
                $progress->advance();
            });
        });

        $progress->finish();

        return 0;
    }

    protected function rebuildStates(Transaction $tx): void
    {
        $tx->fromState?->delete();
        $tx->toState?->delete();

        $tx->from?->balance($tx->currency)->logState($tx);
        $tx->to?->balance($tx->currency)->logState($tx);
    }
}
