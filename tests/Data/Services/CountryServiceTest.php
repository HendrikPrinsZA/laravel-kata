<?php

use App\Enums\CountryCode;
use App\Models\Country;
use App\Services\CountryService;

it('can get exchange rates aggregates', function (CountryCode $countryCode) {
    $country = Country::firstWhere('code', $countryCode);
    $countryService = app()->make(CountryService::class);
    $aggregates = $countryService->getExchangeRatesAggregates($country);

    expect($aggregates)->toMatchSnapshot();
})->with([
    CountryCode::ZA,
    CountryCode::NL,
]);
