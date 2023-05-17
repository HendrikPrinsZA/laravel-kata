<?php

use App\Enums\CountryCode;
use App\Models\Country;
use App\Services\CountryService;

it('can get exchange rates aggregates', function () {
    $country = Country::firstWhere('code', CountryCode::ZA);
    $countryService = app()->make(CountryService::class);
    $aggregates = $countryService->getExchangeRatesAggregates($country);

    expect($aggregates)->toMatchArray([
        'avg' => 16.80926,
        'sum' => 30710.518,
        'min' => 14.158,
        'max' => 20.872,
        'count' => 1827,
    ]);
});
