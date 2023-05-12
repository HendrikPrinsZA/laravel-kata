<?php

namespace Database\Seeders;

use Database\Seeders\Environments\LocalSeeder;
use Database\Seeders\Environments\ProductionSeeder;
use Database\Seeders\Environments\TestingSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // We expect the base `users` table
        if (! Schema::hasTable('users')) {
            Artisan::call('migrate:fresh', [
                '--database' => config('database.connections.laravel.database'),
            ]);

            return;
        }

        $this->call(DefaultSeeder::class);

        switch (app()->environment()) {
            case 'local':
                $this->call(LocalSeeder::class);
                break;
            case 'testing':
                $this->call(TestingSeeder::class);
                break;
            case 'staging':
            case 'production':
                $this->call(ProductionSeeder::class);
                break;
        }
    }
}
