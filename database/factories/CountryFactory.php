<?php

namespace Database\Factories;

use App\Enums\CountryCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    public function definition()
    {
        $countries = CountryCode::all(); // This is wrong!
        $countryCode = $this->faker->randomElement($countries->keys());

        return $countries[$countryCode];
    }
}
