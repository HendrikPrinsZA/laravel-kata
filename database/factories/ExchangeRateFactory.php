<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    public function definition()
    {
        return [
            'base_currency_id' => Currency::factory(),
            'target_currency_id' => Currency::factory(),
            'rate' => $this->faker->randomFloat(4, 0, 3),
            'date' => $this->faker->date(),
        ];
    }
}
