<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase: Remove legacy division + custom roles concept.
 *
 * 1. Drop division_id from users, departments, kpi_components
 * 2. Drop divisions table
 * 3. Drop role_id (FK to custom roles) from users, kpi_indicators
 * 4. Drop custom roles table (Spatie creates its own 'roles' table in the next migration)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Drop division_id indexes + FK + column from users ─────────────
        if (Schema::hasColumn('users', 'division_id')) {
            Schema::table('users', function (Blueprint $table) {
                // Drop composite index that includes division_id (SQLite requires this first)
                try { $table->dropIndex('idx_users_org_role'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        // ── 2. Drop division_id index + FK + column from departments ──────────
        if (Schema::hasColumn('departments', 'division_id')) {
            Schema::table('departments', function (Blueprint $table) {
                try { $table->dropIndex('idx_dept_division_active'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        // ── 3. Drop division_id index + FK + column from kpi_components ───────
        if (Schema::hasColumn('kpi_components', 'division_id')) {
            Schema::table('kpi_components', function (Blueprint $table) {
                try { $table->dropIndex('idx_kpi_comp_div_active'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        // ── 4. Drop division_id index + FK from kpis table ───────────────────
        if (Schema::hasColumn('kpis', 'division_id')) {
            Schema::table('kpis', function (Blueprint $table) {
                try { $table->dropIndex('idx_kpis_div_active'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        // ── 5. Drop division_id index + FK from leaderboard table ────────────
        if (Schema::hasTable('leaderboard') && Schema::hasColumn('leaderboard', 'division_id')) {
            Schema::table('leaderboard', function (Blueprint $table) {
                try { $table->dropIndex('idx_leader_period_div'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
                if (Schema::hasColumn('leaderboard', 'rank_in_division')) {
                    $table->dropColumn('rank_in_division');
                }
            });
        }

        // ── 6. Drop divisions table (no more references) ──────────────────────
        Schema::dropIfExists('divisions');

        // ── 7. Drop role_id index + FK from kpi_indicators ────────────────────
        if (Schema::hasColumn('kpi_indicators', 'role_id')) {
            Schema::table('kpi_indicators', function (Blueprint $table) {
                try { $table->dropIndex('kpi_indicators_role_id_index'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('role_id');
            });
        }

        // ── 8. Drop role_id (FK to custom roles) from users ───────────────────
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }

        // ── 8b. Drop role_id (FK to custom roles) from kpi_scores ─────────────
        if (Schema::hasTable('kpi_scores') && Schema::hasColumn('kpi_scores', 'role_id')) {
            Schema::table('kpi_scores', function (Blueprint $table) {
                try { $table->dropIndex('kpi_scores_role_id_period_type_period_start_index'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('role_id');
            });
        }

        // ── 9. Drop custom roles table (Spatie creates its own 'roles' table next)
        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        // Recreate divisions table (minimal schema for rollback)
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('kode', 20)->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recreate custom roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
};
