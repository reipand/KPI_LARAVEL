<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_reports', function (Blueprint $table) {
            $table->foreignId('kpi_indicator_id')
                ->nullable()
                ->after('kpi_component_id')
                ->constrained('kpi_indicators')
                ->nullOnDelete();
        });

        Schema::table('kpi_reports', function (Blueprint $table) {
            $table->dropForeign(['kpi_component_id']);
            $table->dropColumn('kpi_component_id');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_reports', function (Blueprint $table) {
            $table->dropForeign(['kpi_indicator_id']);
            $table->dropColumn('kpi_indicator_id');
            $table->foreignId('kpi_component_id')->nullable()->constrained('kpi_components')->nullOnDelete();
        });
    }
};
