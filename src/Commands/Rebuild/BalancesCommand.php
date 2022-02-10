<?php

namespace O21\LaravelWallet\Commands\Rebuild;

use Illuminate\Console\Command;
use O21\LaravelWallet\Contracts\BalanceContract;

class BalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '021-wallet:rebuild-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild wallet balances';

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
        $balanceClass = app(BalanceContract::class);

        $progress = $this->output->createProgressBar($balanceClass::count());
        $progress->start();

        $balanceClass::chunk(100, static function ($balances) use ($progress) {
            $balances->each(function(BalanceContract $balance) use ($progress) {
                $balance->recalculate();
                $progress->advance();
            });
        });

        $progress->finish();

        return 0;
    }
}
