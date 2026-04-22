<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KPI Templates — configurable, template-based KPI engine.
 * A template groups multiple KpiTemplateIndicators and can be
 * assigned to departments, positions, or individual employees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('template_name');
            $table->text('description')->nullable();
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'is_active'], 'idx_kpi_tpl_tenant_active');
            $table->index(['tenant_id', 'department_id'], 'idx_kpi_tpl_dept');
        });

        Schema::create('kpi_template_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('kpi_template_id');
            $table->string('indicator_name');
            $table->decimal('weight', 5, 2)->comment('0.00–100.00; sum per template must equal 100');
            $table->enum('target_type', ['number', 'percent', 'boolean', 'checklist', 'rating'])
                ->default('number');
            $table->decimal('target_value', 20, 4)->default(0);
            $table->enum('scoring_method', ['higher_is_better', 'lower_is_better', 'exact_match'])
                ->default('higher_is_better');
            $table->decimal('max_cap', 8, 2)->default(120.00)
                ->comment('Max achievement % capped at this value');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('kpi_template_id')->references('id')->on('kpi_templates')->cascadeOnDelete();

            $table->index(['tenant_id', 'kpi_template_id'], 'idx_kpi_tpl_ind_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_template_indicators');
        Schema::dropIfExists('kpi_templates');
    }
};
