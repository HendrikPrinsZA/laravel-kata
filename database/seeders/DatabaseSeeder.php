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
        $this->command->info('Database Seeder');
        $this->command->table([
            'Variable',
            'Value',
        ], [
            [
                'Dummy data / Max users',
                config('laravel-kata.dummy-data.max-users'),
            ],
            [
                'Dummy data / Max blogs per user',
                config('laravel-kata.dummy-data.max-user-blogs'),
            ],
        ]);

        if (! Schema::hasTable('users')) {
            Artisan::call('migrate:fresh');
        }

        $this->call(DefaultSeeder::class);

        match (app()->environment()) {
            'local' => $this->call(LocalSeeder::class),
            'testing' => $this->call(TestingSeeder::class),
            'staging',
            'production' => $this->call(ProductionSeeder::class),
        };
    }
}
