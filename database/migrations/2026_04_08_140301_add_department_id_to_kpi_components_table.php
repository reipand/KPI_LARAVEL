<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_components', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('jabatan')
                ->constrained('departments')
                ->nullOnDelete();

            $table->index(['department_id', 'is_active'], 'idx_kpi_comp_dept_active');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_components', function (Blueprint $table) {
            $table->dropIndex('idx_kpi_comp_dept_active');
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
