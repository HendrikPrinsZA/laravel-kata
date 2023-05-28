<?php

use App\Enums\CountryCode;
use App\Models\Country;
use App\Services\CountryService;

it('can get exchange rates aggregates', function (
    CountryCode $countryCode,
    array $expectedAggregates
) {
    $country = Country::firstWhere('code', $countryCode);
    $countryService = app()->make(CountryService::class);
    $aggregates = $countryService->getExchangeRatesAggregates($country);

    // Keep: to realign the data set
    // dump($aggregates);

    expect($aggregates)->toMatchArray($expectedAggregates);
})->with('country-exchange-rates');
