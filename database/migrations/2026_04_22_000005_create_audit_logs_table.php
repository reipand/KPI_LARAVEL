<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('module_name', 60);
            $table->string('action_name', 60);
            $table->string('entity_name', 60);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_value_json')->nullable();
            $table->json('new_value_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'module_name', 'created_at'], 'idx_audit_tenant_module');
            $table->index(['tenant_id', 'entity_name', 'entity_id'], 'idx_audit_entity');
            $table->index(['user_id', 'created_at'], 'idx_audit_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
