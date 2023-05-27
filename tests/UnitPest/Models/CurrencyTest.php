<?php

use App\Models\Country;
use App\Models\Currency;

it('can get countries', function () {
    $currency = Currency::first();

    expect($currency)
        ->toBeInstanceOf(Currency::class)
        ->id->not->toBeNull()
        ->countries->toContainOnlyInstancesOf(Country::class);
});
