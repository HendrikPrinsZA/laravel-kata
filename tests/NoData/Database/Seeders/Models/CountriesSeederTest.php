<?php

use App\Models\Country;
use Database\Seeders\Models\CountriesSeeder;
use Database\Seeders\Models\CurrenciesSeeder;

it('can seed with currencies', function () {
    $this->seed(CurrenciesSeeder::class);
    $this->seed(CountriesSeeder::class);

    expect(Country::count())
        ->toBeGreaterThan(0);
});

it('fails on no currencies', function () {
    expect(
        fn () => $this->seed(CountriesSeeder::class)
    )->toThrow(Exception::class);
});
