<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add normalized FK columns to users.
 * ADDITIVE ONLY — old 'jabatan' and 'departemen' string columns are PRESERVED
 * so existing application code keeps working during the migration window.
 *
 * After backfilling data via NormalizationSeeder, old columns can be dropped
 * in a separate migration once application code is updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Normalized FK: replaces free-text 'departemen'
            $table->foreignId('department_id')
                ->nullable()
                ->after('departemen')
                ->constrained('departments')
                ->nullOnDelete();

            // Normalized FK: replaces free-text 'jabatan'
            $table->foreignId('position_id')
                ->nullable()
                ->after('department_id')
                ->constrained('positions')
                ->nullOnDelete();

            $table->index(['department_id', 'role'], 'idx_users_org_role');
            $table->index(['position_id', 'status_karyawan'], 'idx_users_pos_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_org_role');
            $table->dropIndex('idx_users_pos_status');
            $table->dropForeign(['position_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'position_id']);
        });
    }
};
