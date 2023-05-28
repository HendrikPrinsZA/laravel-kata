<?php

use App\Models\ExchangeRate;
use Database\Seeders\Models\CurrenciesSeeder;
use Database\Seeders\Models\ExchangeRatesSeeder;

it('can seed', function () {
    $this->seed(CurrenciesSeeder::class);
    $this->seed(ExchangeRatesSeeder::class);

    expect(ExchangeRate::count())->toBe(1464);
    expect(ExchangeRate::all()->avg('rate'))
        ->toBe(6.007963797814225);
});

// TODO: Test with clearing the cache
// - Note: We shoulod not reach out to the external
//         service, use ExchangeRateService::API_HOST to mock
