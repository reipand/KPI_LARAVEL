<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_components', function (Blueprint $table) {
            $table->enum('kpi_type', ['number', 'percentage', 'boolean'])
                ->nullable()
                ->after('tipe');
            $table->enum('period', ['daily', 'weekly', 'monthly'])
                ->default('monthly')
                ->after('kpi_type');
            $table->string('satuan', 50)->nullable()->after('target');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_components', function (Blueprint $table) {
            $table->dropColumn(['kpi_type', 'period', 'satuan']);
        });
    }
};
