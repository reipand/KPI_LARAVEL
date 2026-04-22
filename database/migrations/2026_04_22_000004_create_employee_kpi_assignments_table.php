<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee-level KPI assignments from templates.
 * Separate from the existing kpi_assignments (which are indicator-level).
 * This table represents "HR assigns KPI Template X to Employee Y for Period Z".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_kpi_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('kpi_template_id');
            $table->unsignedBigInteger('assigned_by');
            $table->unsignedSmallInteger('period_month')->nullable()->comment('1–12; null for non-monthly');
            $table->unsignedSmallInteger('period_year');
            $table->enum('status', [
                'draft',
                'assigned',
                'submitted',
                'reviewed',
                'approved',
                'rejected',
            ])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('kpi_template_id')->references('id')->on('kpi_templates')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['tenant_id', 'employee_id', 'kpi_template_id', 'period_year', 'period_month'],
                'uniq_emp_kpi_period'
            );
            $table->index(['tenant_id', 'status'], 'idx_emp_kpi_tenant_status');
            $table->index(['tenant_id', 'employee_id', 'period_year'], 'idx_emp_kpi_period');
        });

        Schema::create('employee_kpi_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('indicator_id')->comment('kpi_template_indicators.id');
            $table->decimal('actual_value', 20, 4)->nullable();
            $table->decimal('achievement_percent', 8, 2)->nullable()
                ->comment('Capped at indicator max_cap');
            $table->decimal('weighted_score', 8, 4)->nullable()
                ->comment('achievement_percent * weight / 100');
            $table->text('employee_notes')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('assignment_id')->references('id')->on('employee_kpi_assignments')->cascadeOnDelete();
            $table->foreign('indicator_id')->references('id')->on('kpi_template_indicators')->cascadeOnDelete();

            $table->unique(['assignment_id', 'indicator_id'], 'uniq_result_assign_indicator');
            $table->index(['tenant_id', 'assignment_id'], 'idx_emp_kpi_results_assign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_kpi_results');
        Schema::dropIfExists('employee_kpi_assignments');
    }
};
