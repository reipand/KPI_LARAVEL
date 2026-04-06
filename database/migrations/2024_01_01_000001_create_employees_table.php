<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('name');
            $table->string('position'); // jabatan
            $table->string('department');
            $table->enum('status', ['Kontrak', 'Tetap']);
            $table->date('join_date');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->enum('role', ['employee', 'hr_manager', 'director'])->default('employee');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
