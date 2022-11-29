<?php

namespace Database\Seeders;

use App\Collections\CountryCollection;
use App\Enums\CountryCode;
use App\Models\Country;

class CountriesSeeder extends BaseSeeder
{
    public function seed(): void
    {
        $countries = CountryCollection::make();
        CountryCode::all()->each(
            fn (array $details) => $countries->push(Country::factory()->make($details))
        );

        $countries->upsert();
    }
}
