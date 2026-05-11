<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sla', 'tenant_id')) {
            return;
        }

        Schema::table('sla', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->index('tenant_id', 'idx_sla_tenant');
        });

        $defaultTenantId = (int) DB::table('tenants')->orderBy('id')->value('id');

        DB::statement("
            UPDATE sla
            LEFT JOIN positions ON positions.id = sla.position_id
            SET sla.tenant_id = COALESCE(positions.tenant_id, {$defaultTenantId})
            WHERE sla.tenant_id IS NULL
        ");

        Schema::table('sla', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sla', 'tenant_id')) {
            return;
        }

        Schema::table('sla', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex('idx_sla_tenant');
            $table->dropColumn('tenant_id');
        });
    }
};
