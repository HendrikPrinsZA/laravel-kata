<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;

class KataTestCommand extends Command
{
    protected $signature = 'kata:test';

    protected $description = 'Kata test command';

    public function handle(): int
    {
        if (! $this->testConnection()) {
            return self::FAILURE;
        }

        if (! $this->testConnection('testing')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function testConnection($connection = 'mysql'): bool
    {
        try {
            /** @var MySqlConnection $connection */
            $connection = DB::connection($connection);
            $connection->getPDO();
            $connection->getDatabaseName();
        } catch (Exception $e) {
            $this->warn($e->getMessage());

            return false;
        }

        $this->info(sprintf('Database: %s', $connection->getDatabaseName()));

        return true;
    }
}
