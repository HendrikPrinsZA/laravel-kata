<?php

use App\Models\ExchangeRate;
use Database\Seeders\Models\ExchangeRatesSeeder;

it('can seed', function () {
    ExchangeRate::truncate();
    $this->seed(ExchangeRatesSeeder::class);

    expect(ExchangeRate::count())->toBe(7275);
    expect(ExchangeRate::all()->avg('rate'))->toBe(5.775995601374562);
});
