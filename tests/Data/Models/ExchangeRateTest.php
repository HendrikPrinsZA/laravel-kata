<?php

use App\Models\Currency;
use App\Models\ExchangeRate;

it('can get first', function () {
    $record = ExchangeRate::first();

    expect($record)
        ->toBeInstanceOf(ExchangeRate::class)
        ->id->not->toBeNull()
        ->baseCurrency->toBeInstanceOf(Currency::class)
        ->targetCurrency->toBeInstanceOf(Currency::class);
});
