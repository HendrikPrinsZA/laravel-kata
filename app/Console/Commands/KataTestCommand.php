<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KataTestCommand extends Command
{
    protected $signature = 'kata:test';

    protected $description = 'Kata test command';

    public function handle(): int
    {
        $connectionInterface = DB::connection();
        $this->info(sprintf(
            'Database: %s', $connectionInterface->getDatabaseName()
        ));

        $connectionInterface = DB::connection('testing');
        $this->info(sprintf(
            'Database (tests): %s', $connectionInterface->getDatabaseName()
        ));

        return 0;
    }
}
