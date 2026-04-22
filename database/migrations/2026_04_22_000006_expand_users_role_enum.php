<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expand the users.role ENUM to include multi-tenant roles.
 * Using ALTER COLUMN to avoid data loss on existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM modification — must list ALL valid values
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

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role` ENUM('pegawai','hr_manager','direktur') NOT NULL DEFAULT 'pegawai'
        ");
    }
};
