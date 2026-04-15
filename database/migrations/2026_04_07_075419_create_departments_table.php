<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NORMALIZATION: departments
 * Replaces free-text 'departemen' column on users.
 * 6 departments: BOD, FNA, HGA, BDV, RND, ITD
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->string('nama', 100);
            $table->string('kode', 20)->unique()->comment('Short code, e.g. HGA, FNA, BOD, ITD');

            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
