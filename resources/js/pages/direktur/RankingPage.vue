<script setup>
import { ref, computed, onMounted } from 'vue';
import { useKpiStore } from '@/stores/kpi';
import { useKpiColor } from '@/composables/useKpiColor';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { downloadFile } from '@/services/api';

const kpiStore  = useKpiStore();
const { getPredikat } = useKpiColor();

const search      = ref('');
const sortField   = ref('rank');
const sortDir     = ref('asc');

async function fetchAll() {
    await kpiStore.fetchRanking();
}

onMounted(fetchAll);
const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(fetchAll, { interval: 30_000 });

// ── Filtered & sorted list ────────────────────────────────────────────────────
const filtered = computed(() => {
    let list = kpiStore.ranking;
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(r =>
            r.name?.toLowerCase().includes(q) ||
            r.position?.toLowerCase().includes(q)
        );
    }
    return [...list].sort((a, b) => {
        let va = a[sortField.value], vb = b[sortField.value];
        if (typeof va === 'string') va = va?.toLowerCase();
        if (typeof vb === 'string') vb = vb?.toLowerCase();
        if (va < vb) return sortDir.value === 'asc' ? -1 : 1;
        if (va > vb) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
});

function toggleSort(field) {
    if (sortField.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDir.value = field === 'rank' ? 'asc' : 'desc';
    }
}

const sortIcon = (field) => {
    if (sortField.value !== field) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
};

// ── Summary stats ─────────────────────────────────────────────────────────────
const stats = computed(() => {
    const all = kpiStore.ranking;
    if (!all.length) return { avg: 0, best: null, worst: null, total: 0 };
    const avg  = Math.round((all.reduce((s, r) => s + r.kpi_score, 0) / all.length) * 10) / 10;
    const best  = all[0];
    const worst = all[all.length - 1];
    return { avg, best, worst, total: all.length };
});

// ── Export ────────────────────────────────────────────────────────────────────
async function exportCsv() {
    await downloadFile('/export/ranking/csv', {
        fallbackFilename: `ranking-kpi-${new Date().toISOString().slice(0, 10)}.csv`,
    });
}

function predikatBadgeClass(score) {
    if (score >= 5)  return 'bg-emerald-100 text-emerald-700';
    if (score >= 4)  return 'bg-blue-100 text-blue-700';
    if (score >= 3)  return 'bg-amber-100 text-amber-700';
    if (score >= 2)  return 'bg-orange-100 text-orange-700';
    return 'bg-red-100 text-red-700';
}

function rankMedal(rank) {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return null;
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">Direktur</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Ranking KPI Pegawai</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Peringkat seluruh pegawai berdasarkan nilai KPI bulan berjalan.
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span v-if="lastUpdated" class="text-[11px] text-white/50">{{ formatTime(lastUpdated) }}</span>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white/70 transition hover:bg-white/20"
                        :class="{ 'animate-spin': isRefreshing }"
                        title="Refresh"
                        @click="refresh"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </section>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="dashboard-panel p-5">
                <p class="section-heading">Total Pegawai</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.total }}</p>
            </div>
            <div class="dashboard-panel p-5">
                <p class="section-heading">Rata-rata KPI</p>
                <p class="mt-2 text-3xl font-bold"
                   :class="stats.avg >= 4 ? 'text-emerald-600' : stats.avg >= 3 ? 'text-blue-600' : 'text-amber-600'">
                    {{ stats.avg }}
                </p>
            </div>
            <div class="dashboard-panel p-5">
                <p class="section-heading">Tertinggi</p>
                <p class="mt-2 text-sm font-bold text-slate-900 truncate">{{ stats.best?.name ?? '-' }}</p>
                <p class="text-xs text-emerald-600">KPI: {{ stats.best?.kpi_score ?? '-' }}</p>
            </div>
            <div class="dashboard-panel p-5">
                <p class="section-heading">Perlu Perhatian</p>
                <p class="mt-2 text-sm font-bold text-slate-900 truncate">{{ stats.worst?.name ?? '-' }}</p>
                <p class="text-xs text-red-500">KPI: {{ stats.worst?.kpi_score ?? '-' }}</p>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="flex flex-wrap items-center gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Cari nama / jabatan..."
                class="form-input !w-auto min-w-[200px]"
            />
            <div class="ml-auto text-xs text-slate-400">{{ filtered.length }} pegawai</div>
            <button class="btn-secondary text-xs" @click="exportCsv">
                <svg class="mr-1.5 inline h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export CSV
            </button>
        </div>

        <!-- Table -->
        <div class="dashboard-panel overflow-hidden">
            <!-- Loading -->
            <div v-if="kpiStore.isLoading" class="space-y-2 p-6">
                <Skeleton v-for="i in 10" :key="i" class="h-14 rounded-lg" />
            </div>

            <!-- Empty -->
            <div v-else-if="!filtered.length" class="py-20 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">📊</div>
                <p class="text-sm text-slate-500">Tidak ada data ranking</p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                            <th
                                class="cursor-pointer select-none px-5 py-3 text-left hover:text-slate-800"
                                @click="toggleSort('rank')"
                            >
                                Rank {{ sortIcon('rank') }}
                            </th>
                            <th
                                class="cursor-pointer select-none px-5 py-3 text-left hover:text-slate-800"
                                @click="toggleSort('name')"
                            >
                                Nama {{ sortIcon('name') }}
                            </th>
                            <th class="px-5 py-3 text-left">Jabatan</th>
                            <th
                                class="cursor-pointer select-none px-5 py-3 text-right hover:text-slate-800"
                                @click="toggleSort('kpi_score')"
                            >
                                Nilai KPI {{ sortIcon('kpi_score') }}
                            </th>
                            <th class="px-5 py-3 text-left">Predikat</th>
                            <th class="px-5 py-3 text-left">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="item in filtered"
                            :key="item.user_id"
                            class="transition-colors hover:bg-slate-50"
                        >
                            <!-- Rank -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                                        :class="item.rank <= 3 ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ rankMedal(item.rank) || ('#' + item.rank) }}
                                    </div>
                                </div>
                            </td>
                            <!-- Nama -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">
                                        {{ (item.name || 'U').slice(0, 1).toUpperCase() }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ item.name }}</span>
                                </div>
                            </td>
                            <!-- Jabatan -->
                            <td class="px-5 py-3.5 text-xs text-slate-500">{{ item.position ?? '-' }}</td>
                            <!-- Nilai -->
                            <td class="px-5 py-3.5 text-right font-bold text-slate-900">{{ item.kpi_score }}</td>
                            <!-- Predikat -->
                            <td class="px-5 py-3.5">
                                <span :class="['inline-block rounded-full px-2.5 py-0.5 text-[11px] font-semibold', predikatBadgeClass(item.kpi_score)]">
                                    {{ item.predikat?.label ?? getPredikat(item.kpi_score).label }}
                                </span>
                            </td>
                            <!-- Progress bar -->
                            <td class="px-5 py-3.5">
                                <div class="w-32">
                                    <div class="h-2 rounded-full bg-slate-200">
                                        <div
                                            class="h-2 rounded-full transition-all duration-500"
                                            :class="item.kpi_score >= 4 ? 'bg-emerald-500' : item.kpi_score >= 3 ? 'bg-blue-500' : item.kpi_score >= 2 ? 'bg-amber-500' : 'bg-red-500'"
                                            :style="{ width: Math.min(100, (item.kpi_score / 5) * 100) + '%' }"
                                        />
                                    </div>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ Math.round((item.kpi_score / 5) * 100) }}% dari max</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
