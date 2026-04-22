<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Seeds the 6 standard multi-tenant roles.
 * These complement the existing position-based Spatie roles.
 */
class MultiTenantRoleSeeder extends Seeder
{
    private const ROLES = [
        'super_admin'  => 'Full cross-tenant access',
        'tenant_admin' => 'Full access within own tenant',
        'hr_manager'   => 'HR operations and KPI management',
        'dept_head'    => 'Department-level KPI review',
        'supervisor'   => 'Team task and KPI review',
        'employee'     => 'Personal KPI submission and task completion',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $name => $description) {
            Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
        }

        $this->command->info('Multi-tenant roles seeded: '.implode(', ', array_keys(self::ROLES)));
    }
}
