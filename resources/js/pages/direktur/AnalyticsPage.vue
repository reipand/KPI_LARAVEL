<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAnalyticsStore } from '@/stores/analytics';
import { useKpiStore } from '@/stores/kpi';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import LineChart from '@/components/charts/LineChart.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DoughnutChart from '@/components/charts/DoughnutChart.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { downloadFile } from '@/services/api';

const store    = useAnalyticsStore();
const kpiStore = useKpiStore();

const yearOptions = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const selectedYear = ref(new Date().getFullYear());

async function applyFilter() {
    store.setFilter('tahun', selectedYear.value);
    await Promise.all([store.fetchAll(), kpiStore.fetchRanking()]);
}

onMounted(() => applyFilter());
const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(applyFilter, { interval: 60_000 });

// ── All chart data as computed so they react to store updates ─────────────────

const trendChart = computed(() => {
    const raw = store.trend;
    if (!raw?.labels?.length) return { labels: [], datasets: [] };
    return {
        labels: raw.labels,
        datasets: (raw.datasets ?? []).map((ds, i) => ({
            label: ds.label,
            data:  ds.data,
            color: ['#6366f1', '#10b981'][i] ?? '#6366f1',
            fill:  i === 0,
        })),
    };
});

const divisionChart = computed(() => {
    const raw = store.perDivision;
    if (!raw?.labels?.length) return { labels: [], datasets: [] };
    return {
        labels: raw.labels,
        datasets: (raw.datasets ?? []).map((ds, i) => ({
            label: ds.label,
            data:  ds.data,
            color: ['#8b5cf6', '#10b981'][i] ?? '#8b5cf6',
        })),
    };
});

const distributionChart = computed(() => {
    const raw = store.distribution?.report_based;
    if (!raw?.labels?.length) return { labels: [], data: [], colors: [] };
    const colors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'];
    return {
        labels: raw.labels,
        data:   raw.data,
        colors: raw.labels.map((_, i) => colors[i] ?? '#94a3b8'),
    };
});

const taskDistChart = computed(() => {
    const raw = store.distribution?.task_based;
    if (!raw?.labels?.length) return { labels: [], data: [], colors: [] };
    const colorMap = {
        'Baik Sekali': '#22c55e', 'Baik': '#3b82f6',
        'Cukup': '#f59e0b', 'Kurang': '#ef4444', 'Buruk': '#dc2626',
    };
    return {
        labels: raw.labels,
        data:   raw.data,
        colors: raw.labels.map(l => colorMap[l] ?? '#94a3b8'),
    };
});

// Chart keys
const trendKey        = computed(() => trendChart.value.labels.join(','));
const divisionKey     = computed(() => divisionChart.value.labels.join(','));
const distributionKey = computed(() => distributionChart.value.data.join(','));
const taskDistKey     = computed(() => taskDistChart.value.data.join(','));

// Overview cards
const overviewCards = computed(() => [
    {
        label: 'Total Karyawan',
        value: store.overview?.total_employees ?? 0,
        sub:   `${store.overview?.total_divisions ?? 0} divisi aktif`,
        icon:  `<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>`,
        color: 'text-slate-700', bg: 'bg-slate-100',
    },
    {
        label: 'Avg. Achievement',
        value: store.overview?.avg_achievement != null ? parseFloat(store.overview.avg_achievement).toFixed(1) + '%' : '-',
        sub:   `${store.overview?.total_reports ?? 0} laporan bulan ini`,
        icon:  `<path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-6"/>`,
        color: 'text-indigo-700', bg: 'bg-indigo-100',
    },
    {
        label: 'Excellent + Good',
        value: (store.overview?.excellent_count ?? 0) + (store.overview?.good_count ?? 0),
        sub:   'performa baik (≥80%)',
        icon:  `<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>`,
        color: 'text-green-700', bg: 'bg-green-100',
    },
    {
        label: 'Perlu Perhatian',
        value: store.overview?.bad_count ?? 0,
        sub:   'performa buruk (<50%)',
        icon:  `<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>`,
        color: 'text-red-700', bg: 'bg-red-100',
    },
]);

// Top 5 ranking
const topRanking = computed(() => kpiStore.ranking.slice(0, 5));

async function exportRankingCsv() {
    await downloadFile('/export/ranking/csv', {
        params: { tahun: selectedYear.value },
        fallbackFilename: `ranking-kpi-${selectedYear.value}.csv`,
    });
}

async function exportReportsCsv() {
    await downloadFile('/export/reports/csv', {
        params: { tahun: selectedYear.value },
        fallbackFilename: `laporan-kpi-${selectedYear.value}.csv`,
    });
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">Executive View</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Analytics & Insights</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Ringkasan eksekutif performa KPI organisasi — tren, distribusi, dan benchmarking antar divisi.
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
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
                    <select
                        v-model="selectedYear"
                        class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/30"
                        @change="applyFilter"
                    >
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button class="btn-secondary !border-white/20 !bg-white/10 !text-white hover:!bg-white/20" @click="exportRankingCsv">
                        Export Ranking
                    </button>
                </div>
            </div>
        </section>

        <!-- Overview cards -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <template v-if="store.isLoadingOverview">
                <Skeleton v-for="i in 4" :key="i" class="h-28 rounded-2xl" />
            </template>
            <template v-else>
                <div v-for="card in overviewCards" :key="card.label" class="dashboard-panel flex items-center gap-4 p-5">
                    <div :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', card.bg]">
                        <svg :class="['h-5 w-5', card.color]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="card.icon" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">{{ card.label }}</p>
                        <p :class="['mt-0.5 text-2xl font-bold', card.color]">{{ card.value }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ card.sub }}</p>
                    </div>
                </div>
            </template>
        </section>

        <!-- Trend + Ranking -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="dashboard-panel overflow-hidden lg:col-span-2">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Tren Organisasi</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Achievement Rate Bulanan {{ selectedYear }}</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.isLoadingTrend" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        <svg class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"/>
                        </svg>
                        Memuat...
                    </div>
                    <div v-else-if="!trendChart.labels.length" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        Belum ada data trend.
                    </div>
                    <LineChart
                        v-else
                        :key="trendKey"
                        :labels="trendChart.labels"
                        :datasets="trendChart.datasets"
                        y-label="Nilai"
                        :height="280"
                    />
                </div>
            </div>

            <!-- Top 5 ranking sidebar -->
            <div class="dashboard-panel overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Top Performer</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Ranking Terbaik</h3>
                </div>
                <div class="p-4">
                    <template v-if="kpiStore.isLoading">
                        <div class="space-y-2">
                            <Skeleton v-for="i in 5" :key="i" class="h-12 rounded-xl" />
                        </div>
                    </template>
                    <div v-else-if="!topRanking.length" class="py-10 text-center text-sm text-slate-400">
                        Belum ada data ranking.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="item in topRanking" :key="item.user_id" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-50">
                            <div :class="['flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold',
                                item.rank === 1 ? 'bg-amber-400 text-white' :
                                item.rank === 2 ? 'bg-slate-400 text-white' :
                                item.rank === 3 ? 'bg-orange-400 text-white' : 'bg-slate-100 text-slate-600']">
                                #{{ item.rank }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ item.position }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-slate-700">{{ item.kpi_score }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Division bar + Distribution -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="dashboard-panel overflow-hidden lg:col-span-2">
                <div class="border-b border-slate-200 px-6 py-4">
                    <p class="section-heading">Benchmarking Divisi</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Rata-rata Achievement per Divisi</h3>
                </div>
                <div class="p-6">
                    <div v-if="store.isLoadingDivision" class="flex h-64 items-center justify-center text-sm text-slate-400">Memuat...</div>
                    <div v-else-if="!divisionChart.labels.length" class="flex h-64 items-center justify-center text-sm text-slate-400">
                        Belum ada data divisi.
                    </div>
                    <BarChart
                        v-else
                        :key="divisionKey"
                        :labels="divisionChart.labels"
                        :datasets="divisionChart.datasets"
                        y-label="Nilai"
                        :height="300"
                    />
                </div>
            </div>

            <div class="space-y-6">
                <div class="dashboard-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <p class="section-heading">Distribusi Laporan</p>
                        <h3 class="mt-1 text-base font-bold text-slate-900">Sebaran Pencapaian</h3>
                    </div>
                    <div class="p-4">
                        <div v-if="store.isLoadingDistribution" class="flex h-48 items-center justify-center text-sm text-slate-400">Memuat...</div>
                        <div v-else-if="!distributionChart.labels.length" class="flex h-48 items-center justify-center text-sm text-slate-400">
                            Belum ada data.
                        </div>
                        <DoughnutChart
                            v-else
                            :key="distributionKey"
                            :labels="distributionChart.labels"
                            :data="distributionChart.data"
                            :colors="distributionChart.colors"
                            :height="190"
                        />
                    </div>
                </div>

                <div class="dashboard-panel overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <p class="section-heading">Distribusi Predikat</p>
                        <h3 class="mt-1 text-base font-bold text-slate-900">Skor KPI Pegawai</h3>
                    </div>
                    <div class="p-4">
                        <div v-if="store.isLoadingDistribution" class="flex h-48 items-center justify-center text-sm text-slate-400">Memuat...</div>
                        <div v-else-if="!taskDistChart.labels.length" class="flex h-48 items-center justify-center text-sm text-slate-400">
                            Belum ada data.
                        </div>
                        <DoughnutChart
                            v-else
                            :key="taskDistKey"
                            :labels="taskDistChart.labels"
                            :data="taskDistChart.data"
                            :colors="taskDistChart.colors"
                            :height="190"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Export -->
        <div class="dashboard-panel">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Export Data</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">Unduh Laporan</h3>
            </div>
            <div class="flex flex-wrap gap-3 p-6">
                <button class="btn-secondary" @click="exportRankingCsv">
                    <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 15V3m0 12-4-4m4 4 4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                    </svg>
                    Export Ranking (CSV)
                </button>
                <button class="btn-secondary" @click="exportReportsCsv">
                    <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 15V3m0 12-4-4m4 4 4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17"/>
                    </svg>
                    Export Laporan KPI (CSV)
                </button>
            </div>
        </div>
    </AppLayout>
</template>
