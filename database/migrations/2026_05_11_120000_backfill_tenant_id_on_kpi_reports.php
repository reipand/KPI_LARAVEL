<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_reports') || ! Schema::hasColumn('kpi_reports', 'tenant_id')) {
            return;
        }

        DB::statement('
            UPDATE kpi_reports
            INNER JOIN users ON users.id = kpi_reports.user_id
            SET kpi_reports.tenant_id = users.tenant_id
            WHERE kpi_reports.tenant_id IS NULL
              AND users.tenant_id IS NOT NULL
        ');

        DB::statement('
            UPDATE kpi_reports
            INNER JOIN kpi_indicators ON kpi_indicators.id = kpi_reports.kpi_indicator_id
            SET kpi_reports.tenant_id = kpi_indicators.tenant_id
            WHERE kpi_reports.tenant_id IS NULL
              AND kpi_indicators.tenant_id IS NOT NULL
        ');

        $defaultTenantId = DB::table('tenants')->orderBy('id')->value('id');
        if ($defaultTenantId) {
            DB::table('kpi_reports')
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $defaultTenantId]);
        }
    }

    public function down(): void
    {
        // Data backfill only; no rollback.
    }
};
