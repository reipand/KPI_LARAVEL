<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('kpi_indicator_id')
                ->nullable()
                ->after('kpi_component_id')
                ->constrained('kpi_indicators')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\KpiIndicator::class);
            $table->dropColumn('kpi_indicator_id');
        });
    }
};
