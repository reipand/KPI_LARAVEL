<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop legacy custom roles table and role_id columns.
 * Also drops any residual division_id columns for safety on existing databases.
 * Spatie permission tables are created in the next migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Safety: drop any residual division_id columns on existing databases ─
        if (Schema::hasColumn('users', 'division_id')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropIndex('idx_users_org_role'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        if (Schema::hasColumn('departments', 'division_id')) {
            Schema::table('departments', function (Blueprint $table) {
                try { $table->dropIndex('idx_dept_division_active'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        if (Schema::hasColumn('kpi_components', 'division_id')) {
            Schema::table('kpi_components', function (Blueprint $table) {
                try { $table->dropIndex('idx_kpi_comp_div_active'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        if (Schema::hasColumn('kpis', 'division_id')) {
            Schema::table('kpis', function (Blueprint $table) {
                try { $table->dropIndex('idx_kpis_div_active'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
            });
        }

        if (Schema::hasTable('leaderboard') && Schema::hasColumn('leaderboard', 'division_id')) {
            Schema::table('leaderboard', function (Blueprint $table) {
                try { $table->dropIndex('idx_leader_period_div'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('division_id');
                if (Schema::hasColumn('leaderboard', 'rank_in_division')) {
                    $table->dropColumn('rank_in_division');
                }
            });
        }

        Schema::dropIfExists('divisions');

        // ── Drop role_id from kpi_indicators ─────────────────────────────────
        if (Schema::hasColumn('kpi_indicators', 'role_id')) {
            Schema::table('kpi_indicators', function (Blueprint $table) {
                try { $table->dropIndex('kpi_indicators_role_id_index'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('role_id');
            });
        }

        // ── Drop role_id from users ───────────────────────────────────────────
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }

        // ── Drop role_id from kpi_scores ──────────────────────────────────────
        if (Schema::hasTable('kpi_scores') && Schema::hasColumn('kpi_scores', 'role_id')) {
            Schema::table('kpi_scores', function (Blueprint $table) {
                try { $table->dropIndex('kpi_scores_role_id_period_type_period_start_index'); } catch (\Throwable) {}
                $table->dropConstrainedForeignId('role_id');
            });
        }

        // ── Drop custom roles table (Spatie creates its own in next migration) ─
        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        // no-op — not reversible after Spatie migration runs
    }
};
