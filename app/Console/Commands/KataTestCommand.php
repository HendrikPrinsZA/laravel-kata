<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KataTestCommand extends Command
{
    protected $signature = 'kata:test';

    protected $description = 'Kata test command';

    public function handle(): int
    {
        $this->testConnection();
        $this->testConnection('testing');

        return 0;
    }

    protected function testConnection($connection = 'mysql'): bool
    {
        $connected = false;
        $connectionInterface = DB::connection($connection);

        try {
            $connectionInterface->getPDO();
            $connected = true;
        } catch (Exception $e) {
            $this->warn($e->getMessage());
        }

        if (! $connected) {
            $this->warn(sprintf('Database: %s', $connectionInterface->getDatabaseName()));

            return false;
        }

        $this->info(sprintf('Database: %s', $connectionInterface->getDatabaseName()));

        return true;
    }
}
