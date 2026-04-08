<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useKpiReportStore } from '@/stores/kpiReport';
import { useEmployeeStore } from '@/stores/employee';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Alert from '@/components/ui/Alert.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import { useToast } from '@/composables/useToast';

const reportStore = useKpiReportStore();
const empStore = useEmployeeStore();
const toast = useToast();

const filterStatus = ref('submitted');
const filterUserId = ref('');
const filterBulan = ref(new Date().getMonth() + 1);
const filterTahun = ref(new Date().getFullYear());
const filterScore = ref('');
const searchQuery = ref('');
const sortKey = ref('tanggal_desc');

const reviewDialog = reactive({
    open: false,
    report: null,
    action: '',
    catatan: '',
    loading: false,
    error: '',
});

const detailDialog = reactive({
    open: false,
    report: null,
});

const evidenceDialog = reactive({
    open: false,
    url: '',
    name: '',
});

const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const years = computed(() => {
    const year = new Date().getFullYear();
    return [year - 1, year, year + 1];
});

function loadReports() {
    reportStore.fetchReports({
        status: filterStatus.value || undefined,
        user_id: filterUserId.value || undefined,
        bulan: filterBulan.value,
        tahun: filterTahun.value,
        per_page: 100,
    });
}

onMounted(() => {
    empStore.fetchEmployees();
    loadReports();
});

watch([filterStatus, filterUserId, filterBulan, filterTahun], loadReports);

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(loadReports, { interval: 20_000 });

const statusCounts = computed(() => {
    const counts = {
        all: reportStore.reports.length,
        draft: 0,
        submitted: 0,
        approved: 0,
        rejected: 0,
    };

    reportStore.reports.forEach((report) => {
        if (counts[report.status] !== undefined) {
            counts[report.status] += 1;
        }
    });

    return counts;
});

const summaryCards = computed(() => {
    const percentages = reportStore.reports
        .map((report) => Number(report.persentase))
        .filter((value) => Number.isFinite(value));

    const averageAchievement = percentages.length
        ? (percentages.reduce((sum, value) => sum + value, 0) / percentages.length).toFixed(1)
        : null;

    const withEvidence = reportStore.reports.filter((report) => Boolean(report.file_evidence_url)).length;

    return [
        {
            label: 'Menunggu Review',
            value: statusCounts.value.submitted,
            hint: 'laporan siap ditindaklanjuti',
            tone: 'text-amber-700 bg-amber-50 border-amber-200',
        },
        {
            label: 'Disetujui',
            value: statusCounts.value.approved,
            hint: 'laporan lolos verifikasi',
            tone: 'text-emerald-700 bg-emerald-50 border-emerald-200',
        },
        {
            label: 'Ditolak',
            value: statusCounts.value.rejected,
            hint: 'perlu perbaikan dari pegawai',
            tone: 'text-rose-700 bg-rose-50 border-rose-200',
        },
        {
            label: 'Rata-rata Capaian',
            value: averageAchievement ? `${averageAchievement}%` : '-',
            hint: `${withEvidence} laporan memiliki evidence`,
            tone: 'text-sky-700 bg-sky-50 border-sky-200',
        },
    ];
});

const filteredReports = computed(() => {
    let items = [...reportStore.reports];

    if (searchQuery.value.trim()) {
        const query = searchQuery.value.trim().toLowerCase();
        items = items.filter((report) => {
            const haystacks = [
                report.user?.nama,
                report.user?.jabatan,
                report.kpi_component?.objectives,
                report.period_label,
                report.catatan,
            ];

            return haystacks.some((value) => String(value || '').toLowerCase().includes(query));
        });
    }

    if (filterScore.value) {
        items = items.filter((report) => report.score_label === filterScore.value);
    }

    items.sort((left, right) => {
        switch (sortKey.value) {
        case 'tanggal_asc':
            return String(left.tanggal || '').localeCompare(String(right.tanggal || ''));
        case 'persentase_desc':
            return Number(right.persentase ?? -1) - Number(left.persentase ?? -1);
        case 'persentase_asc':
            return Number(left.persentase ?? -1) - Number(right.persentase ?? -1);
        case 'employee_asc':
            return String(left.user?.nama || '').localeCompare(String(right.user?.nama || ''));
        case 'status_asc':
            return String(left.status || '').localeCompare(String(right.status || ''));
        case 'tanggal_desc':
        default:
            return String(right.tanggal || '').localeCompare(String(left.tanggal || ''));
        }
    });

    return items;
});

const pendingReports = computed(() =>
    filteredReports.value.filter((report) => report.status === 'submitted')
);

const selectedReportMetrics = computed(() => {
    const report = detailDialog.report ?? reviewDialog.report;
    if (!report) return [];

    return [
        { label: 'Nilai Target', value: report.nilai_target ?? '-' },
        { label: 'Nilai Aktual', value: report.nilai_aktual ?? '-' },
        { label: 'Persentase', value: report.persentase != null ? `${report.persentase}%` : '-' },
        { label: 'Periode', value: report.period_label || '-' },
        { label: 'Tanggal', value: formatDate(report.tanggal) },
        { label: 'Status', value: statusLabel(report.status) },
        { label: 'Diajukan', value: report.submitted_at ? formatDate(report.submitted_at) : '-' },
    ];
});

function openReview(report, action) {
    reviewDialog.report = report;
    reviewDialog.action = action;
    reviewDialog.catatan = action === 'approved' ? (report.review_note || '') : '';
    reviewDialog.error = '';
    reviewDialog.open = true;
}

function openDetail(report) {
    detailDialog.report = report;
    detailDialog.open = true;
}

async function submitReview() {
    if (!reviewDialog.report) return;

    if (reviewDialog.action === 'rejected' && !reviewDialog.catatan.trim()) {
        reviewDialog.error = 'Catatan wajib diisi saat menolak laporan.';
        return;
    }

    reviewDialog.loading = true;
    reviewDialog.error = '';

    try {
        const updated = await reportStore.reviewReport(
            reviewDialog.report.id,
            reviewDialog.action,
            reviewDialog.catatan.trim(),
        );

        if (detailDialog.report?.id === updated.id) {
            detailDialog.report = updated;
        }

        toast.success(reviewDialog.action === 'approved' ? 'Laporan disetujui.' : 'Laporan ditolak.');
        reviewDialog.open = false;
    } catch (err) {
        reviewDialog.error = err.response?.data?.message || 'Gagal memproses review.';
    } finally {
        reviewDialog.loading = false;
    }
}

function viewEvidence(report) {
    evidenceDialog.url = report.file_evidence_url;
    evidenceDialog.name = report.kpi_component?.objectives || 'Evidence';
    evidenceDialog.open = true;
}

function applyStatus(status) {
    filterStatus.value = status;
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function scoreBadge(scoreLabel) {
    const map = {
        excellent: 'badge-success',
        good: 'badge-info',
        average: 'badge-warning',
        bad: 'badge-danger',
    };

    return map[scoreLabel] || 'badge-neutral';
}

function scoreLabel(scoreLabelValue) {
    const map = {
        excellent: 'Sangat Baik',
        good: 'Baik',
        average: 'Cukup',
        bad: 'Kurang',
    };

    return map[scoreLabelValue] || scoreLabelValue || '-';
}

function statusBadge(status) {
    const map = {
        draft: 'badge-neutral',
        submitted: 'badge-warning',
        approved: 'badge-success',
        rejected: 'badge-danger',
    };

    return map[status] || 'badge-neutral';
}

function statusLabel(status) {
    const map = {
        draft: 'Draft',
        submitted: 'Menunggu Review',
        approved: 'Disetujui',
        rejected: 'Ditolak',
    };

    return map[status] || status;
}

function percentageTone(value) {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) return 'text-slate-500';
    if (numeric >= 100) return 'text-emerald-600';
    if (numeric >= 80) return 'text-sky-600';
    if (numeric >= 50) return 'text-amber-600';
    return 'text-rose-600';
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">HR - Review Laporan</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Tinjau Laporan KPI</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                        Verifikasi laporan KPI pegawai berdasarkan field yang mereka isi: komponen KPI, periode, tanggal, target, aktual, catatan, dan evidence.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="lastUpdated" class="text-xs text-white/55">{{ formatTime(lastUpdated) }}</span>
                    <button class="btn-secondary !border-white/20 !bg-white/10 !text-white hover:!bg-white/20" @click="refresh">
                        <svg class="h-4 w-4" :class="{ 'animate-spin': isRefreshing }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 4v6h6M23 20v-6h-6" />
                            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div v-for="card in summaryCards" :key="card.label" :class="['rounded-2xl border p-5', card.tone]">
                <p class="text-xs font-semibold uppercase tracking-[0.14em]">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-bold">{{ card.value }}</p>
                <p class="mt-1 text-xs opacity-80">{{ card.hint }}</p>
            </div>
        </section>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="section-heading">Kontrol Review</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">Filter dan prioritas laporan</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button class="btn-secondary text-xs" :class="{ '!bg-slate-900 !text-white': filterStatus === '' }" @click="applyStatus('')">
                            Semua ({{ statusCounts.all }})
                        </button>
                        <button class="btn-secondary text-xs" :class="{ '!bg-amber-500 !text-white': filterStatus === 'submitted' }" @click="applyStatus('submitted')">
                            Menunggu ({{ statusCounts.submitted }})
                        </button>
                        <button class="btn-secondary text-xs" :class="{ '!bg-emerald-600 !text-white': filterStatus === 'approved' }" @click="applyStatus('approved')">
                            Disetujui ({{ statusCounts.approved }})
                        </button>
                        <button class="btn-secondary text-xs" :class="{ '!bg-rose-600 !text-white': filterStatus === 'rejected' }" @click="applyStatus('rejected')">
                            Ditolak ({{ statusCounts.rejected }})
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label class="form-label">Cari laporan</label>
                    <Input v-model="searchQuery" placeholder="Cari nama, jabatan, komponen KPI, periode, atau catatan..." />
                </div>

                <div>
                    <label class="form-label">Pegawai</label>
                    <select v-model="filterUserId" class="form-input">
                        <option value="">Semua Pegawai</option>
                        <option v-for="employee in empStore.employees" :key="employee.id" :value="employee.id">
                            {{ employee.nama }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Kategori Skor</label>
                    <select v-model="filterScore" class="form-input">
                        <option value="">Semua Kategori</option>
                        <option value="excellent">Sangat Baik</option>
                        <option value="good">Baik</option>
                        <option value="average">Cukup</option>
                        <option value="bad">Kurang</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Bulan</label>
                    <select v-model="filterBulan" class="form-input">
                        <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Tahun</label>
                    <select v-model="filterTahun" class="form-input">
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="form-label">Urutkan</label>
                    <select v-model="sortKey" class="form-input">
                        <option value="tanggal_desc">Tanggal terbaru</option>
                        <option value="tanggal_asc">Tanggal terlama</option>
                        <option value="persentase_desc">Capaian tertinggi</option>
                        <option value="persentase_asc">Capaian terendah</option>
                        <option value="employee_asc">Nama pegawai A-Z</option>
                        <option value="status_asc">Status A-Z</option>
                    </select>
                </div>

                <div class="lg:col-span-4">
                    <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Hasil filter</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ filteredReports.length }} laporan</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Antrian review aktif</p>
                            <p class="mt-1 text-lg font-bold text-amber-600">{{ pendingReports.length }} laporan</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Pagination API</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ reportStore.pagination.total }} total data</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-base font-bold text-slate-900">
                    Daftar Laporan
                    <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                        {{ filteredReports.length }}
                    </span>
                </h3>
            </div>

            <template v-if="reportStore.isLoading">
                <div class="space-y-3 p-6">
                    <Skeleton v-for="i in 6" :key="i" class="h-24 rounded-2xl" />
                </div>
            </template>

            <div v-else-if="!filteredReports.length" class="py-16 text-center text-sm text-slate-400">
                Tidak ada laporan yang cocok dengan filter saat ini.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="report in filteredReports"
                    :key="report.id"
                    class="flex flex-col gap-4 px-6 py-5 transition-colors hover:bg-slate-50 xl:flex-row xl:items-start"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <Avatar :name="report.user?.nama || '?'" size="sm" class="mt-0.5 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ report.user?.nama || '-' }}</span>
                                <span :class="statusBadge(report.status)">{{ statusLabel(report.status) }}</span>
                                <span v-if="report.score_label" :class="scoreBadge(report.score_label)">{{ scoreLabel(report.score_label) }}</span>
                                <span v-if="report.file_evidence_url" class="badge-info">Ada Evidence</span>
                            </div>

                            <div class="mt-1 text-sm text-slate-700">
                                {{ report.kpi_component?.objectives || '-' }}
                            </div>

                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                                <span>{{ report.user?.jabatan || '-' }}</span>
                                <span>{{ formatDate(report.tanggal) }}</span>
                                <span>{{ report.period_label || '-' }}</span>
                                <span>{{ report.period_type || '-' }}</span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 text-xs md:grid-cols-4">
                                <div class="rounded-xl bg-slate-100 px-3 py-2">
                                    <div class="text-slate-500">Nilai Target</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ report.nilai_target ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-slate-100 px-3 py-2">
                                    <div class="text-slate-500">Nilai Aktual</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ report.nilai_aktual ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-slate-100 px-3 py-2">
                                    <div class="text-slate-500">Capaian</div>
                                    <div :class="['mt-1 font-semibold', percentageTone(report.persentase)]">
                                        {{ report.persentase != null ? `${report.persentase}%` : '-' }}
                                    </div>
                                </div>
                                <div class="rounded-xl bg-slate-100 px-3 py-2">
                                    <div class="text-slate-500">Catatan Pegawai</div>
                                    <div class="mt-1 line-clamp-1 font-semibold text-slate-900">{{ report.catatan || '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2 xl:w-[270px] xl:justify-end">
                        <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openDetail(report)">Detail</button>

                        <button
                            v-if="report.file_evidence_url"
                            class="btn-secondary !px-3 !py-1.5 text-xs"
                            @click="viewEvidence(report)"
                        >
                            Evidence
                        </button>

                        <template v-if="report.status === 'submitted'">
                            <button class="btn-primary !px-3 !py-1.5 text-xs" @click="openReview(report, 'approved')">
                                Setujui
                            </button>
                            <button class="btn-danger !px-3 !py-1.5 text-xs" @click="openReview(report, 'rejected')">
                                Tolak
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <Dialog
            v-model:open="reviewDialog.open"
            :title="reviewDialog.action === 'approved' ? 'Setujui Laporan' : 'Tolak Laporan'"
            class="max-w-2xl"
        >
            <template v-if="reviewDialog.report">
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Pegawai</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ reviewDialog.report.user?.nama || '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ reviewDialog.report.user?.jabatan || '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Komponen KPI</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ reviewDialog.report.kpi_component?.objectives || '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ reviewDialog.report.period_label || '-' }} · {{ reviewDialog.report.period_type || '-' }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3">
                    <div v-for="metric in selectedReportMetrics" :key="metric.label" class="rounded-xl bg-slate-100 px-3 py-2 text-xs">
                        <div class="text-slate-500">{{ metric.label }}</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ metric.value }}</div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Catatan Pegawai</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ reviewDialog.report.catatan || 'Pegawai belum menambahkan catatan.' }}</p>
                </div>

                <Alert v-if="reviewDialog.error" variant="danger" class="mt-4">{{ reviewDialog.error }}</Alert>

                <div class="mt-4">
                    <label class="form-label">
                        Catatan Reviewer
                        <span v-if="reviewDialog.action === 'rejected'" class="text-red-500">*</span>
                        <span v-else class="text-xs text-slate-400">(opsional)</span>
                    </label>
                    <Textarea
                        v-model="reviewDialog.catatan"
                        rows="4"
                        :placeholder="reviewDialog.action === 'rejected'
                            ? 'Jelaskan alasan penolakan dan apa yang harus diperbaiki pegawai...'
                            : 'Tambahkan catatan verifikasi atau arahan tambahan untuk pegawai...'"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button class="btn-secondary" :disabled="reviewDialog.loading" @click="reviewDialog.open = false">Batal</button>
                    <button
                        :class="reviewDialog.action === 'approved' ? 'btn-primary' : 'btn-danger'"
                        :disabled="reviewDialog.loading"
                        @click="submitReview"
                    >
                        {{ reviewDialog.loading ? 'Memproses...' : (reviewDialog.action === 'approved' ? 'Setujui Laporan' : 'Tolak Laporan') }}
                    </button>
                </div>
            </template>
        </Dialog>

        <Dialog v-model:open="detailDialog.open" title="Detail Laporan KPI" class="max-w-3xl">
            <template v-if="detailDialog.report">
                <div class="mt-3 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <Avatar :name="detailDialog.report.user?.nama || '?'" size="sm" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900">{{ detailDialog.report.user?.nama || '-' }}</span>
                            <span :class="statusBadge(detailDialog.report.status)">{{ statusLabel(detailDialog.report.status) }}</span>
                            <span v-if="detailDialog.report.score_label" :class="scoreBadge(detailDialog.report.score_label)">
                                {{ scoreLabel(detailDialog.report.score_label) }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">{{ detailDialog.report.kpi_component?.objectives || '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ detailDialog.report.user?.jabatan || '-' }} · {{ detailDialog.report.period_label || '-' }} · {{ detailDialog.report.period_type || '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3">
                    <div v-for="metric in selectedReportMetrics" :key="metric.label" class="rounded-xl bg-slate-100 px-3 py-2 text-xs">
                        <div class="text-slate-500">{{ metric.label }}</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ metric.value }}</div>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Catatan Pegawai</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ detailDialog.report.catatan || 'Pegawai belum menambahkan catatan.' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Catatan Reviewer</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ detailDialog.report.review_note || 'Belum ada catatan reviewer.' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Evidence</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button
                                v-if="detailDialog.report.file_evidence_url"
                                class="btn-secondary text-xs"
                                @click="viewEvidence(detailDialog.report)"
                            >
                                Lihat Evidence
                            </button>
                            <span v-else class="text-sm text-slate-400">Belum ada file evidence.</span>

                            <template v-if="detailDialog.report.status === 'submitted'">
                                <button class="btn-primary text-xs" @click="openReview(detailDialog.report, 'approved')">Setujui</button>
                                <button class="btn-danger text-xs" @click="openReview(detailDialog.report, 'rejected')">Tolak</button>
                            </template>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Metadata Pengajuan</p>
                        <div class="mt-3 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                            <div>
                                <span class="font-medium text-slate-900">Tanggal laporan:</span>
                                {{ formatDate(detailDialog.report.tanggal) }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900">Waktu submit:</span>
                                {{ detailDialog.report.submitted_at ? formatDate(detailDialog.report.submitted_at) : '-' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900">Waktu review:</span>
                                {{ detailDialog.report.reviewed_at ? formatDate(detailDialog.report.reviewed_at) : '-' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900">Status:</span>
                                {{ statusLabel(detailDialog.report.status) }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900">Kategori skor:</span>
                                {{ scoreLabel(detailDialog.report.score_label) }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Dialog>

        <Dialog v-model:open="evidenceDialog.open" title="File Evidence" class="max-w-2xl">
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                <img
                    v-if="/\.(png|jpe?g)$/i.test(evidenceDialog.url)"
                    :src="evidenceDialog.url"
                    :alt="evidenceDialog.name"
                    class="w-full object-contain"
                />
                <iframe
                    v-else-if="/\.pdf$/i.test(evidenceDialog.url)"
                    :src="evidenceDialog.url"
                    class="h-[60vh] w-full"
                    title="Evidence PDF"
                />
                <div v-else class="p-6 text-center">
                    <a :href="evidenceDialog.url" target="_blank" class="btn-primary">Unduh File Evidence</a>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <a :href="evidenceDialog.url" target="_blank" class="btn-secondary text-xs">Buka di tab baru</a>
            </div>
        </Dialog>
    </AppLayout>
</template>
