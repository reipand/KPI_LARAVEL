<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nip' => fake()->unique()->numerify('EMP####'),
            'nama' => fake()->name(),
            'jabatan' => fake()->jobTitle(),
            'departemen' => fake()->randomElement(['Operasional', 'Finance', 'HR']),
            'status_karyawan' => fake()->randomElement(['tetap', 'kontrak']),
            'tanggal_masuk' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'no_hp' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'pegawai',
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
        ]);
    }
}
