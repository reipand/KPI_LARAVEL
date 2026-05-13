<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->tinyInteger('review_quality')->nullable()->after('non_kpi_category');
            $table->tinyInteger('review_timeliness')->nullable()->after('review_quality');
            $table->tinyInteger('review_initiative')->nullable()->after('review_timeliness');
            $table->tinyInteger('review_contribution')->nullable()->after('review_initiative');
            $table->text('review_note')->nullable()->after('review_contribution');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'review_quality',
                'review_timeliness',
                'review_initiative',
                'review_contribution',
                'review_note',
            ]);
        });
    }
};
