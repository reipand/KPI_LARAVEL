<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });

        Schema::create('kpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2);
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->index('role_id');
        });

        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('kpi_indicators')->cascadeOnDelete();
            $table->enum('period_type', ['weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_value', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['indicator_id', 'period_type', 'period_start'], 'kpi_targets_period_unique');
        });

        Schema::create('kpi_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained('kpi_indicators')->cascadeOnDelete();
            $table->enum('period_type', ['weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_value', 12, 2)->default(0);
            $table->decimal('actual_value', 12, 2)->default(0);
            $table->decimal('achievement_ratio', 8, 4)->default(0);
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'indicator_id', 'period_type', 'period_start'], 'kpi_records_period_unique');
            $table->index(['period_type', 'period_start']);
        });

        Schema::create('kpi_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->enum('period_type', ['weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('normalized_score', 8, 2)->default(0);
            $table->string('grade', 10)->default('E');
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_type', 'period_start'], 'kpi_scores_period_unique');
            $table->index(['role_id', 'period_type', 'period_start']);
        });

        DB::table('roles')->insert([
            [
                'name' => 'Pegawai',
                'slug' => 'employee',
                'description' => 'Default employee role',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HR Manager',
                'slug' => 'hr_manager',
                'description' => 'Human resource manager',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Direktur',
                'slug' => 'direktur',
                'description' => 'Executive role',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $roleMap = DB::table('roles')->pluck('id', 'slug');

        DB::table('users')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($roleMap) {
                foreach ($users as $user) {
                    if (isset($roleMap[$user->role])) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['role_id' => $roleMap[$user->role]]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_scores');
        Schema::dropIfExists('kpi_records');
        Schema::dropIfExists('kpi_targets');
        Schema::dropIfExists('kpi_indicators');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
