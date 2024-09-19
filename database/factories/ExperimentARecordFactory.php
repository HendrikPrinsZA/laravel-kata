<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExperimentARecord>
 */
class ExperimentARecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unique_field_1' => $this->faker->unique()->word(),
            'unique_field_2' => $this->faker->unique()->word(),
            'unique_field_3' => $this->faker->unique()->word(),
            'update_field_1' => $this->faker->word(),
            'update_field_2' => $this->faker->word(),
            'update_field_3' => $this->faker->word(),
        ];
    }
}
