<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Creates a Super Admin user with no tenant_id (cross-tenant access).
 * Change credentials before deploying to production.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureSuperAdminRoleIsAllowed();

        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'superadmin@system.local'],
            [
                'nip'             => 'SYS-ADMIN-001',
                'nama'            => 'System Administrator',
                'jabatan'         => 'Super Admin',
                'departemen'      => 'System',
                'status_karyawan' => 'Tetap',
                'tanggal_masuk'   => '2024-01-01',
                'email'           => 'superadmin@system.local',
                'password'        => Hash::make('SuperAdmin@2026!'),
                'role'            => 'super_admin',
                'tenant_id'       => null,
            ]
        );

        $user->syncRoles(['super_admin']);

        $this->command->info('Super Admin created: superadmin@system.local / SuperAdmin@2026!');
    }

    private function ensureSuperAdminRoleIsAllowed(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `users` WHERE Field = 'role'");

        if (! $column || str_contains((string) $column->Type, "'super_admin'")) {
            return;
        }

        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM(
                'employee',
                'hr_manager',
                'direktur',
                'super_admin',
                'tenant_admin',
                'dept_head',
                'supervisor'
            ) NOT NULL DEFAULT 'employee'
        ");
    }
}
