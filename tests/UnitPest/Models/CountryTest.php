<?php

use App\Enums\CountryCode;
use App\Models\Country;

it('can get exchange rates aggregates', function () {
    $country = Country::firstWhere('code', CountryCode::ZA);
    $country->setExchangeRatesAggregates();
    expect($country)
        ->toBeInstanceOf(Country::class)
        ->exchangeRatesAvg->toBe(16.80926)
        ->exchangeRatesSum->toBe(30710.518)
        ->exchangeRatesMin->toBe(14.158)
        ->exchangeRatesMax->toBe(20.872)
        ->exchangeRatesCount->toBe(1827);
});

it('can make model', function () {
    $country = Country::factory()->make();

    expect($country)
        ->toBeInstanceOf(Country::class)
        ->id->toBeNull();
});
