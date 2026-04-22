<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_code', 20)->unique();
            $table->string('tenant_name');
            $table->string('domain')->nullable()->unique();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('logo_url')->nullable();
            $table->string('primary_color', 10)->nullable()->default('#2563EB');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('address')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('tenant_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
