<?php

namespace Database\Seeders\Models;

use App\Collections\CountryCollection;
use App\Enums\CountryCode;
use App\Models\Country;
use App\Models\Currency;

class CountriesSeeder extends ModelSeeder
{
    public function seed(): void
    {
        if (app()->runningUnitTests()) {
            if (Currency::count() === 0) {
                $this->seed(CurrenciesSeeder::class);
            }
        }

        $countries = CountryCollection::make();
        CountryCode::all()->each(
            fn (array $details) => $countries->push(Country::make($details))
        );

        $countries->upsert();
    }
}
