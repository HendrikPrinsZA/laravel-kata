<?php

namespace Database\Factories;

use App\Enums\CurrencyCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition()
    {
        return [
            'code' => $this->faker->randomElement([
                CurrencyCode::EUR,
                CurrencyCode::USD,
                CurrencyCode::ZAR,
            ]),
            'name' => 'Euro',
        ];
    }
}
