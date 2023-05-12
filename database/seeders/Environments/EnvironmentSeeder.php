<?php

namespace Database\Seeders\Environments;

use Illuminate\Database\Seeder;

class EnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info(sprintf(
            '  No %s seeders',
            self::class
        ));
    }
}
