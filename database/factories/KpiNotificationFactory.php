<?php

namespace Database\Factories;

use App\Models\KpiNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KpiNotification> */
class KpiNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type'    => fake()->randomElement(['task_assigned', 'kpi_updated', 'low_performance', 'deadline_reminder']),
            'title'   => fake()->sentence(4),
            'body'    => fake()->sentence(),
            'payload' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
