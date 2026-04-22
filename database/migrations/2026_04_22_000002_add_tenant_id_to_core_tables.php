<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add tenant_id to all shared tables for multi-tenant isolation.
 * tenant_id = NULL means global/super-admin owned records.
 */
return new class extends Migration
{
    private array $tables = [
        'users',
        'departments',
        'positions',
        'employees',
        'kpi_components',
        'kpi_indicators',
        'kpi_assignments',
        'kpi_results',
        'tasks',
        'slas',
        'activity_logs',
        'kpi_notifications',
        'kpi_reports',
        'roles',
    ];

    public function up(): void
    {
        // Create default tenant first so existing data can be linked
        $tenantId = DB::table('tenants')->insertGetId([
            'tenant_code'   => 'BASS-001',
            'tenant_name'   => 'PT. BASS Training Center & Consultant',
            'status'        => 'active',
            'primary_color' => '#2563EB',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $blueprint->index('tenant_id', 'idx_'.$blueprint->getTable().'_tenant');
            });

            // Assign all existing rows to the default tenant
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }

        // Add FK after data backfill
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['tenant_id']);
                $blueprint->dropIndex('idx_'.$blueprint->getTable().'_tenant');
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
