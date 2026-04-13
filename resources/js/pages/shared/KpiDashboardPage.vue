<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Activity, Medal, RefreshCw, TrendingDown, Users } from 'lucide-vue-next';
import AppLayout from '@/components/layout/AppLayout.vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import BarChart from '@/components/charts/BarChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import KpiDetailDialog from '@/components/kpi-dashboard/KpiDetailDialog.vue';
import KpiFilters from '@/components/kpi-dashboard/KpiFilters.vue';
import KpiLeaderboardTable from '@/components/kpi-dashboard/KpiLeaderboardTable.vue';
import KpiSummaryCard from '@/components/kpi-dashboard/KpiSummaryCard.vue';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiDashboardStore } from '@/stores/kpiDashboard';

const props = defineProps({
    persona: {
        type: String,
        default: 'HR Control Center',
    },
});

const employeeStore = useEmployeeStore();
const dashboardStore = useKpiDashboardStore();

const search = ref('');
const currentPage = ref(1);
const sortState = reactive({
    field: 'normalized_score',
    direction: 'desc',
});

const detailDialogOpen = ref(false);

const localFilters = ref({
    period: dashboardStore.filters.period,
    roleId: dashboardStore.filters.roleId,
    employeeId: dashboardStore.filters.employeeId,
});

const summary = computed(() => dashboardStore.summary ?? {
    average_kpi: 0,
    employee_count: 0,
    top_performer: null,
    low_performer: null,
});

const roleOptions = computed(() => {
    const unique = new Map();

    employeeStore.employees.forEach((employee) => {
        const key = employee.role_id || employee.role_ref?.id;
        const label = employee.role_ref?.name || employee.role || employee.jabatan;

        if (key && label && !unique.has(String(key))) {
            unique.set(String(key), { value: String(key), label });
        }
    });

    return [{ value: '', label: 'Semua role' }, ...unique.values()];
});

const employeeOptions = computed(() => {
    const filtered = employeeStore.employees
        .filter((employee) => !localFilters.value.roleId || String(employee.role_id) === String(localFilters.value.roleId))
        .map((employee) => ({
            value: String(employee.id),
            label: employee.nama,
        }));

    return [{ value: '', label: 'Semua karyawan' }, ...filtered];
});

const normalizedRows = computed(() =>
    (dashboardStore.ranking ?? []).map((row, index) => ({
        ...row,
        rank: row.rank || index + 1,
        normalized_score: Number(row.normalized_score ?? 0),
        raw_score: Number(row.raw_score ?? 0),
        role: row.role,
        user: row.user,
    }))
);

const filteredRows = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    let rows = normalizedRows.value.filter((row) => {
        const employeeMatch = !localFilters.value.employeeId || String(row.user?.id) === String(localFilters.value.employeeId);
        const searchMatch = !keyword
            || row.user?.nama?.toLowerCase().includes(keyword)
            || row.role?.name?.toLowerCase().includes(keyword)
            || row.user?.jabatan?.toLowerCase().includes(keyword);

        return employeeMatch && searchMatch;
    });

    rows = [...rows].sort((left, right) => {
        const leftValue = sortState.field === 'role'
            ? (left.role?.name || left.user?.role_ref?.name || '')
            : left[sortState.field];
        const rightValue = sortState.field === 'role'
            ? (right.role?.name || right.user?.role_ref?.name || '')
            : right[sortState.field];

        if (typeof leftValue === 'string' || typeof rightValue === 'string') {
            const a = String(leftValue ?? '').toLowerCase();
            const b = String(rightValue ?? '').toLowerCase();

            if (a < b) return sortState.direction === 'asc' ? -1 : 1;
            if (a > b) return sortState.direction === 'asc' ? 1 : -1;
            return 0;
        }

        const a = Number(leftValue ?? 0);
        const b = Number(rightValue ?? 0);

        return sortState.direction === 'asc' ? a - b : b - a;
    });

    return rows;
});

const chartSeries = computed(() => ({
    labels: dashboardStore.trend.map((point) => point.label),
    average: dashboardStore.trend.map((point) => point.average),
    employees: dashboardStore.trend.map((point) => point.employees),
}));

const teamPerformanceChart = computed(() => {
    const topRows = filteredRows.value.slice(0, 6);

    return {
        labels: topRows.map((row) => row.user?.nama ?? '-'),
        datasets: [
            {
                label: 'KPI Score',
                data: topRows.map((row) => row.normalized_score),
                color: '#0f172a',
            },
        ],
    };
});

const summaryCards = computed(() => [
    {
        title: 'Total Karyawan',
        value: summary.value.employee_count,
        description: 'Jumlah karyawan yang masuk ke kalkulasi KPI pada periode aktif.',
        icon: Users,
        tone: 'info',
        chip: props.persona,
    },
    {
        title: 'Rata-rata KPI',
        value: summary.value.average_kpi,
        description: 'Rerata skor KPI seluruh tim setelah normalisasi maksimal 100.',
        icon: Activity,
        tone: Number(summary.value.average_kpi) >= 80 ? 'success' : Number(summary.value.average_kpi) >= 50 ? 'warning' : 'danger',
        chip: 'Updated live',
    },
    {
        title: 'Top Performer',
        value: summary.value.top_performer?.user?.nama || '-',
        description: summary.value.top_performer
            ? `Skor ${summary.value.top_performer.normalized_score} • Grade ${summary.value.top_performer.grade}`
            : 'Belum ada data performa tertinggi.',
        icon: Medal,
        tone: 'success',
        chip: 'Best',
    },
    {
        title: 'Low Performer',
        value: summary.value.low_performer?.user?.nama || '-',
        description: summary.value.low_performer
            ? `Skor ${summary.value.low_performer.normalized_score} • Grade ${summary.value.low_performer.grade}`
            : 'Belum ada data performa terendah.',
        icon: TrendingDown,
        tone: 'danger',
        chip: 'Needs review',
    },
]);

async function loadPage() {
    await Promise.all([
        employeeStore.fetchEmployees(),
        dashboardStore.hydrate({
            period: localFilters.value.period,
            roleId: localFilters.value.roleId,
        }),
    ]);
}

async function applyFilters() {
    dashboardStore.setFilter('period', localFilters.value.period);
    dashboardStore.setFilter('roleId', localFilters.value.roleId);
    dashboardStore.setFilter('employeeId', localFilters.value.employeeId);
    currentPage.value = 1;

    await dashboardStore.hydrate({
        period: localFilters.value.period,
        roleId: localFilters.value.roleId,
    });

    if (localFilters.value.employeeId) {
        await openDetail(localFilters.value.employeeId);
    }
}

async function openDetail(userId) {
    detailDialogOpen.value = true;
    await dashboardStore.fetchUserDetail(userId, {
        period: localFilters.value.period,
        roleId: localFilters.value.roleId,
    });
}

function handleSort(field) {
    if (sortState.field === field) {
        sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortState.field = field;
    sortState.direction = field === 'role' ? 'asc' : 'desc';
}

watch(search, () => {
    currentPage.value = 1;
});

watch(filteredRows, (rows) => {
    const maxPage = Math.max(1, Math.ceil(rows.length / 8));

    if (currentPage.value > maxPage) {
        currentPage.value = maxPage;
    }
});

watch(() => localFilters.value.roleId, () => {
    if (!employeeOptions.value.some((option) => option.value === localFilters.value.employeeId)) {
        localFilters.value.employeeId = '';
    }
});

onMounted(loadPage);

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(loadPage, { interval: 45_000 });
</script>

<template>
    <AppLayout>
        <section class="relative overflow-hidden rounded-[32px] border border-slate-200/70 bg-[radial-gradient(circle_at_top_left,_rgba(15,23,42,0.1),_transparent_30%),linear-gradient(135deg,_rgba(255,255,255,0.95),_rgba(248,250,252,0.85))] p-6 shadow-sm dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.18),_transparent_28%),linear-gradient(135deg,_rgba(2,6,23,0.98),_rgba(15,23,42,0.88))]">
            <div class="absolute right-0 top-0 h-44 w-44 rounded-full bg-cyan-200/40 blur-3xl dark:bg-cyan-500/10" />
            <div class="absolute bottom-0 left-0 h-40 w-40 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-500/10" />

            <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <Badge variant="outline" class="rounded-full border-slate-200 bg-white/80 px-3 py-1 text-[11px] uppercase tracking-[0.18em] text-slate-600 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300">
                        {{ persona }}
                    </Badge>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                        KPI Dashboard
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Dashboard KPI modern untuk monitoring performa, tren bulanan, dan breakdown indikator per karyawan
                        dalam satu permukaan kerja yang rapi dan data-driven.
                    </p>
                </div>

                <div class="flex items-center gap-3 self-start xl:self-auto">
                    <div class="text-right">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                            Last sync
                        </div>
                        <div class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            {{ lastUpdated ? formatTime(lastUpdated) : 'Baru dibuka' }}
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900"
                        :class="{ 'animate-spin': isRefreshing }"
                        @click="refresh"
                    >
                        <RefreshCw class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </section>

        <div class="mt-6">
            <KpiFilters
                v-model="localFilters"
                :role-options="roleOptions"
                :employee-options="employeeOptions"
                :search="search"
                @update:search="search = $event"
                @apply="applyFilters"
            />
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <KpiSummaryCard
                v-for="card in summaryCards"
                :key="card.title"
                :title="card.title"
                :value="card.value"
                :description="card.description"
                :icon="card.icon"
                :tone="card.tone"
                :chip="card.chip"
            />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <Card class="rounded-[28px] border-slate-200/70 bg-white/85 shadow-sm dark:border-slate-800 dark:bg-slate-950/75">
                <CardHeader class="border-b border-slate-200/70 pb-4 dark:border-slate-800">
                    <CardTitle class="text-base font-semibold">KPI per bulan</CardTitle>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Line chart untuk memantau rata-rata KPI tim selama 6 bulan terakhir.
                    </p>
                </CardHeader>
                <CardContent class="pt-5">
                    <template v-if="dashboardStore.isLoadingTrend">
                        <Skeleton class="h-[280px] rounded-[24px]" />
                    </template>
                    <template v-else>
                        <LineChart
                            :labels="chartSeries.labels"
                            :datasets="[
                                { label: 'Average KPI', data: chartSeries.average, color: '#0f172a', fill: true },
                                { label: 'Employees', data: chartSeries.employees, color: '#10b981' },
                            ]"
                            title=""
                            :height="300"
                            y-label="Score"
                        />
                    </template>
                </CardContent>
            </Card>

            <Card class="rounded-[28px] border-slate-200/70 bg-white/85 shadow-sm dark:border-slate-800 dark:bg-slate-950/75">
                <CardHeader class="border-b border-slate-200/70 pb-4 dark:border-slate-800">
                    <CardTitle class="text-base font-semibold">Performa tim</CardTitle>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Bar chart top performer pada filter yang sedang aktif.
                    </p>
                </CardHeader>
                <CardContent class="pt-5">
                    <template v-if="dashboardStore.isLoadingDashboard">
                        <Skeleton class="h-[280px] rounded-[24px]" />
                    </template>
                    <template v-else>
                        <BarChart
                            :labels="teamPerformanceChart.labels"
                            :datasets="teamPerformanceChart.datasets"
                            :height="300"
                            y-label="KPI Score"
                        />
                    </template>
                </CardContent>
            </Card>
        </div>

        <div class="mt-6">
            <KpiLeaderboardTable
                :rows="filteredRows"
                :loading="dashboardStore.isLoadingDashboard"
                :page="currentPage"
                :sort-field="sortState.field"
                :sort-direction="sortState.direction"
                @sort="handleSort"
                @update:page="currentPage = $event"
                @open-detail="openDetail"
            />
        </div>

        <KpiDetailDialog
            v-model:open="detailDialogOpen"
            :loading="dashboardStore.isLoadingDetail"
            :detail="dashboardStore.detail"
        />
    </AppLayout>
</template>
