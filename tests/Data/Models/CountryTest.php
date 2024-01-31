<?php

use App\Enums\CountryCode;
use App\Models\Country;

it('can get exchange rates aggregates', function (CountryCode $countryCode) {
    /** @var \App\Models\Country $country */
    $country = Country::firstWhere('code', $countryCode);
    $country->setExchangeRatesAggregates();

    expect($country)->toBeInstanceOf(Country::class);

    expect([
        'avg' => $country->exchangeRatesAvg,
        'sum' => $country->exchangeRatesSum,
        'min' => $country->exchangeRatesMin,
        'max' => $country->exchangeRatesMax,
        'count' => $country->exchangeRatesCount,
    ])->toMatchSnapshot();
})->with([
    CountryCode::ZA,
    CountryCode::NL,
]);

it('can make model', function () {
    $country = Country::factory()->make();

    expect($country)
        ->toBeInstanceOf(Country::class)
        ->id->toBeNull();
});
