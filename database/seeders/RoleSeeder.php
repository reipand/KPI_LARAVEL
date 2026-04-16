<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    private const ROLES = [
        // Board of Director
        'Direktur Utama',
        'Direktur',
        // Business Development
        'Marketing & Sales',
        'Digital Marketing',
        // Finance & Accounting
        'Finance',
        'Accounting',
        // HR & GA
        'HR & GA Manager',
        'Driver',
        'Office Boy & Admin GA',
        // Research & Development
        'R&D Staff',
        // Information Technology
        'IT Support',
        'IT Staff',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $name) {
            Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }
    }
}
