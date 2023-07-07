<?php

namespace Database\Seeders;

use Database\Seeders\Models\BlogsSeeder;
use Database\Seeders\Models\CountriesSeeder;
use Database\Seeders\Models\CurrenciesSeeder;
use Database\Seeders\Models\DaysSeeder;
use Database\Seeders\Models\ExchangeRatesSeeder;
use Database\Seeders\Models\UsersSeeder;
use Illuminate\Database\Seeder;

class DefaultSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DaysSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(BlogsSeeder::class);
        $this->call(CurrenciesSeeder::class);
        $this->call(CountriesSeeder::class);
        $this->call(ExchangeRatesSeeder::class);
    }
}
