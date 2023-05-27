<?php

use App\Models\Currency;
use Database\Seeders\Models\CurrenciesSeeder;

it('can seed', function () {
    $this->seed(CurrenciesSeeder::class);

    expect(Currency::count())->toBeGreaterThan(0);
});
