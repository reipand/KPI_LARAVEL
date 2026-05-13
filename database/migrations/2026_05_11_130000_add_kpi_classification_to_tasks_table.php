<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'is_kpi')) {
                $table->boolean('is_kpi')
                    ->default(true)
                    ->after('task_type');
            }

            if (! Schema::hasColumn('tasks', 'non_kpi_category')) {
                $table->string('non_kpi_category', 100)
                    ->nullable()
                    ->after('is_kpi');
            }
        });

        DB::table('tasks')
            ->whereNull('kpi_indicator_id')
            ->update(['is_kpi' => false]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'non_kpi_category')) {
                $table->dropColumn('non_kpi_category');
            }

            if (Schema::hasColumn('tasks', 'is_kpi')) {
                $table->dropColumn('is_kpi');
            }
        });
    }
};
