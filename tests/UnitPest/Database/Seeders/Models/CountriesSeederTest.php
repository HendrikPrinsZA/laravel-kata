<?php

use App\Models\Country;
use Database\Seeders\Models\CountriesSeeder;

it('can seed', function () {
    Country::truncate();
    $this->seed(CountriesSeeder::class);

    expect(Country::count())->toBeGreaterThan(0);
});
