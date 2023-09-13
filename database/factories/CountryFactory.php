<?php

namespace Database\Factories;

use App\Enums\CountryCode;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    public function definition()
    {
        $countryCode = $this->faker->unique()->randomElement(CountryCode::cases());
        $countryCodeDetails = $countryCode->details();

        return [
            'currency_id' => $countryCodeDetails['currency_id'] ?? Currency::factory(),
            'code' => $countryCodeDetails['code'],
            'name' => $countryCodeDetails['name'],
        ];
    }
}
