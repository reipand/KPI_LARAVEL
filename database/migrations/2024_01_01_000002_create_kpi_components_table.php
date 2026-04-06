<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kpi_components', function (Blueprint $table) {
            $table->id();
            $table->string('position'); // jabatan
            $table->string('objective');
            $table->text('strategy');
            $table->decimal('weight', 3, 2); // bobot 0-1
            $table->decimal('target', 15, 2)->nullable();
            $table->string('type'); // zero_delay, zero_error, zero_complaint, achievement, csi
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_components');
    }
};
