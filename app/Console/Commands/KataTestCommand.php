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
        $success = true;
        $config = config(sprintf('database.connections.%s', $connection));
        try {
            /** @var MySqlConnection $connection */
            $connection = DB::connection($connection);
            $connection->getPDO();
            $connection->getDatabaseName();
            $database = $connection->getDatabaseName();
            $success = $database === $config['database'];
        } catch (Exception $exception) {
            $this->warn(sprintf('Database: %s (not connected)', $config['database']));
            $this->warn($exception->getMessage());
            $success = false;
        }

        $this->info(sprintf('Database: %s (connected)', $config['database']));

        return $success;
    }
}
