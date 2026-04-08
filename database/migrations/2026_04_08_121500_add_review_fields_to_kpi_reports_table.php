<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_reports', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('catatan');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_note', 'reviewed_at']);
        });
    }
};
