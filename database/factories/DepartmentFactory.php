<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Department> */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'nama'      => $name,
            'kode'      => strtoupper(Str::random(4)),
            'deskripsi' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
