<?php

namespace Database\Factories;

use App\Models\KpiIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KpiIndicator> */
class KpiIndicatorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                 => fake()->words(3, true),
            'description'          => fake()->sentence(),
            'weight'               => fake()->randomFloat(2, 5, 50),
            'default_target_value' => 100,
            'formula'              => ['type' => 'percentage'],
            'department_id'        => null,
        ];
    }
}
