<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')
            ->where('role', 'pegawai')
            ->update(['role' => 'employee']);

        if (DB::getDriverName() !== 'mysql') {
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

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')
            ->where('role', 'employee')
            ->update(['role' => 'pegawai']);

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM(
                'pegawai',
                'hr_manager',
                'direktur',
                'super_admin',
                'tenant_admin',
                'dept_head',
                'supervisor',
                'employee'
            ) NOT NULL DEFAULT 'pegawai'
        ");
    }
};
