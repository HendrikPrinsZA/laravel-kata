<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends BaseSeeder
{
    use WithoutModelEvents;

    public function seed(): void
    {
        $this->call(UsersSeeder::class);
        $this->call(BlogsSeeder::class);
        $this->call(CurrenciesSeeder::class);
        $this->call(CountriesSeeder::class);
        $this->call(ExchangeRatesSeeder::class);
    }
}
