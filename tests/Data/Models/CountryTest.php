<?php

use App\Enums\CountryCode;
use App\Models\Country;

it('can get exchange rates aggregates', function (
    CountryCode $countryCode,
    array $expectedAggregates
) {
    $country = Country::firstWhere('code', $countryCode);
    $country->setExchangeRatesAggregates();

    expect($country)
        ->toBeInstanceOf(Country::class)
        ->exchangeRatesAvg->toBe($expectedAggregates['avg'])
        ->exchangeRatesSum->toBe($expectedAggregates['sum'])
        ->exchangeRatesMin->toBe($expectedAggregates['min'])
        ->exchangeRatesMax->toBe($expectedAggregates['max'])
        ->exchangeRatesCount->toBe($expectedAggregates['count']);
})->with('country-exchange-rates');

it('can make model', function () {
    $country = Country::factory()->make();

    expect($country)
        ->toBeInstanceOf(Country::class)
        ->id->toBeNull();
});
