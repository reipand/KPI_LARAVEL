<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('task_date');
            $table->string('title');
            $table->string('type')->nullable();
            $table->enum('status', ['Selesai', 'Dalam Proses', 'Pending'])->default('Pending');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('has_delay')->default(false);
            $table->boolean('has_error')->default(false);
            $table->boolean('has_complaint')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
