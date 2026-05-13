<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import { useWorkMonitorStore } from '@/stores/workMonitor';
import { useToast } from '@/composables/useToast';

const store = useWorkMonitorStore();
const toast = useToast();

// ── Tabs & Filters ────────────────────────────────────────────────────────────
const activeTab   = ref('kpi');
const filterBulan = ref('');
const filterTahun = ref('');
const filterStatus = ref('');

const months = [
    { value: 1,  label: 'Januari' },  { value: 2,  label: 'Februari' },
    { value: 3,  label: 'Maret' },    { value: 4,  label: 'April' },
    { value: 5,  label: 'Mei' },      { value: 6,  label: 'Juni' },
    { value: 7,  label: 'Juli' },     { value: 8,  label: 'Agustus' },
    { value: 9,  label: 'September' },{ value: 10, label: 'Oktober' },
    { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];
const years = computed(() => {
    const y = new Date().getFullYear();
    return [y - 1, y, y + 1];
});
const statusOptions = [
    { value: 'Selesai',      label: 'Selesai' },
    { value: 'Dalam Proses', label: 'Dalam Proses' },
    { value: 'Pending',      label: 'Pending' },
];

// ── Non-KPI categories ────────────────────────────────────────────────────────
const categoryLabels = {
    cross_division:      'Bantuan Lintas Divisi',
    incidental:          'Tugas Insidental',
    operational_support: 'Support Operasional',
    problem_solving:     'Problem Solving',
    administration:      'Administrasi Tambahan',
};
const categoryOptions = Object.entries(categoryLabels).map(([value, label]) => ({ value, label }));
const filterCategory = ref('');

// ── Review criteria ───────────────────────────────────────────────────────────
const reviewCriteria = [
    { key: 'review_quality',      label: 'Kualitas' },
    { key: 'review_timeliness',   label: 'Ketepatan Waktu' },
    { key: 'review_initiative',   label: 'Inisiatif' },
    { key: 'review_contribution', label: 'Kontribusi' },
];

// ── Data loading ──────────────────────────────────────────────────────────────
function buildParams() {
    return {
        bulan:  filterBulan.value  || undefined,
        tahun:  filterTahun.value  || undefined,
        status: filterStatus.value || undefined,
    };
}

function loadAll() {
    const params = buildParams();
    store.fetchKpiTasks(params);
    store.fetchNonKpiTasks({ ...params, non_kpi_category: filterCategory.value || undefined });
}

onMounted(loadAll);

watch([filterBulan, filterTahun, filterStatus], loadAll);
watch(filterCategory, () => {
    const params = buildParams();
    store.fetchNonKpiTasks({ ...params, non_kpi_category: filterCategory.value || undefined });
});

// ── Review dialog ─────────────────────────────────────────────────────────────
const reviewDialog = reactive({ open: false, task: null, loading: false, error: '' });
const reviewForm   = reactive({
    review_quality:      null,
    review_timeliness:   null,
    review_initiative:   null,
    review_contribution: null,
    review_note:         '',
});

function openReview(task) {
    reviewDialog.task    = task;
    reviewDialog.error   = '';
    reviewForm.review_quality      = task.review_quality      ?? null;
    reviewForm.review_timeliness   = task.review_timeliness   ?? null;
    reviewForm.review_initiative   = task.review_initiative   ?? null;
    reviewForm.review_contribution = task.review_contribution ?? null;
    reviewForm.review_note         = task.review_note         ?? '';
    reviewDialog.open = true;
}

function setRating(field, value) {
    reviewForm[field] = reviewForm[field] === value ? null : value;
}

async function submitReview() {
    reviewDialog.loading = true;
    reviewDialog.error   = '';
    try {
        await store.submitReview(reviewDialog.task.id, {
            review_quality:      reviewForm.review_quality,
            review_timeliness:   reviewForm.review_timeliness,
            review_initiative:   reviewForm.review_initiative,
            review_contribution: reviewForm.review_contribution,
            review_note:         reviewForm.review_note || null,
        });
        toast.success('Review berhasil disimpan.');
        reviewDialog.open = false;
    } catch (err) {
        reviewDialog.error = err.response?.data?.message || 'Gagal menyimpan review.';
    } finally {
        reviewDialog.loading = false;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function employeeName(task) {
    return task.user?.nama || task.assignee?.nama || '-';
}

function sourceLabel(task) {
    return task.task_type === 'manual_assignment' ? 'HR' : 'Mandiri';
}

function sourceBadgeClass(task) {
    return task.task_type === 'manual_assignment'
        ? 'bg-blue-50 text-blue-700'
        : 'bg-slate-100 text-slate-600';
}

function hasReview(task) {
    return task.review_quality !== null && task.review_quality !== undefined;
}

function avgRating(task) {
    const vals = [task.review_quality, task.review_timeliness, task.review_initiative, task.review_contribution]
        .filter((v) => v != null);
    if (!vals.length) return null;
    return (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1);
}

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatDateRange(task) {
    const start = task.start_date || task.tanggal;
    const end   = task.end_date   || task.tanggal;
    if (!start) return '-';
    if (start === end || !end) return formatDate(start);
    return `${formatDate(start)} – ${formatDate(end)}`;
}

const statusBadge = {
    'Selesai':      'badge-success',
    'Dalam Proses': 'badge-info',
    'Pending':      'badge-warning',
};
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">HR Panel · Monitoring</div>
                    <h2 class="mt-4 text-2xl font-bold md:text-3xl">Monitoring Pekerjaan</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-white/78">
                        Pantau seluruh pekerjaan — baik yang masuk KPI maupun operasional non-KPI — dari semua sumber dalam satu tampilan.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:min-w-[280px]">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Total KPI</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ store.kpiPag.total }}</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Total Non-KPI</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ store.nonKpiPag.total }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tabs -->
        <div class="flex gap-1 rounded-2xl border border-slate-200 bg-slate-50 p-1 w-fit">
            <button
                class="rounded-xl px-5 py-2 text-sm font-semibold transition-all"
                :class="activeTab === 'kpi'
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'"
                @click="activeTab = 'kpi'"
            >
                Pekerjaan KPI
                <span class="ml-1.5 rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-700">
                    {{ store.kpiPag.total }}
                </span>
            </button>
            <button
                class="rounded-xl px-5 py-2 text-sm font-semibold transition-all"
                :class="activeTab === 'nonkpi'
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'"
                @click="activeTab = 'nonkpi'"
            >
                Pekerjaan Non-KPI
                <span class="ml-1.5 rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold text-violet-700">
                    {{ store.nonKpiPag.total }}
                </span>
            </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <select v-model="filterBulan" class="form-input !w-auto">
                <option value="">Semua Bulan</option>
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <select v-model="filterTahun" class="form-input !w-auto">
                <option value="">Semua Tahun</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <select v-model="filterStatus" class="form-input !w-auto">
                <option value="">Semua Status</option>
                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <select v-if="activeTab === 'nonkpi'" v-model="filterCategory" class="form-input !w-auto">
                <option value="">Semua Kategori</option>
                <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
        </div>

        <!-- ── TAB: KPI ──────────────────────────────────────────────────────── -->
        <section v-if="activeTab === 'kpi'" class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-base font-bold text-slate-900">
                    Pekerjaan KPI
                    <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                        {{ store.kpiPag.total }}
                    </span>
                </h3>
                <p class="mt-0.5 text-xs text-slate-500">Semua pekerjaan yang masuk perhitungan KPI, dari penugasan HR maupun input mandiri.</p>
            </div>

            <template v-if="store.loadingKpi">
                <div class="space-y-3 p-6">
                    <Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" />
                </div>
            </template>

            <div v-else-if="!store.kpiTasks.length" class="py-16 text-center text-sm text-slate-400">
                Tidak ada pekerjaan KPI untuk filter ini.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="task in store.kpiTasks"
                    :key="task.id"
                    class="flex flex-col gap-3 px-6 py-4 transition-colors hover:bg-slate-50 sm:flex-row sm:items-start"
                >
                    <Avatar :name="employeeName(task)" size="sm" class="mt-0.5 shrink-0" />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900">{{ task.judul }}</span>
                            <span :class="statusBadge[task.status] || 'badge-neutral'">{{ task.status }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="sourceBadgeClass(task)">
                                {{ sourceLabel(task) }}
                            </span>
                        </div>

                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-slate-500">
                            <span class="font-medium text-slate-700">{{ employeeName(task) }}</span>
                            <span>· {{ formatDateRange(task) }}</span>
                        </div>

                        <p v-if="task.deskripsi" class="mt-1 line-clamp-2 text-xs text-slate-500 leading-relaxed">
                            {{ task.deskripsi }}
                        </p>

                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                            <!-- KPI indicator -->
                            <span v-if="task.kpi_indicator" class="flex items-center gap-1 text-blue-700">
                                <svg class="h-3.5 w-3.5 shrink-0 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/>
                                </svg>
                                <span class="font-medium">{{ task.kpi_indicator.name }}</span>
                                <span v-if="task.kpi_indicator.position" class="text-blue-400">({{ task.kpi_indicator.position.nama }})</span>
                            </span>
                            <span v-else class="flex items-center gap-1 text-amber-600">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                Belum dipetakan ke indikator
                            </span>

                            <!-- Evidence -->
                            <a
                                v-if="task.file_evidence_url"
                                :href="task.file_evidence_url"
                                target="_blank"
                                rel="noreferrer"
                                class="flex items-center gap-1 text-emerald-700 hover:underline"
                            >
                                <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                Lihat Evidence
                            </a>
                            <span v-else class="text-slate-400">Belum ada evidence</span>
                        </div>
                    </div>

                    <!-- Score info -->
                    <div v-if="task.actual_value !== null || task.target_value !== null" class="shrink-0 rounded-xl border bg-slate-50 px-3 py-2 text-right text-xs">
                        <p class="text-slate-400">Realisasi / Target</p>
                        <p class="mt-0.5 font-bold text-slate-800">
                            {{ task.actual_value ?? '—' }} / {{ task.target_value ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── TAB: Non-KPI ──────────────────────────────────────────────────── -->
        <section v-if="activeTab === 'nonkpi'" class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-base font-bold text-slate-900">
                    Pekerjaan Non-KPI
                    <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                        {{ store.nonKpiPag.total }}
                    </span>
                </h3>
                <p class="mt-0.5 text-xs text-slate-500">Pekerjaan operasional dan lintas fungsi yang tidak masuk KPI formal.</p>
            </div>

            <template v-if="store.loadingNonKpi">
                <div class="space-y-3 p-6">
                    <Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" />
                </div>
            </template>

            <div v-else-if="!store.nonKpiTasks.length" class="py-16 text-center text-sm text-slate-400">
                Tidak ada pekerjaan Non-KPI untuk filter ini.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="task in store.nonKpiTasks"
                    :key="task.id"
                    class="flex flex-col gap-3 px-6 py-4 transition-colors hover:bg-slate-50 sm:flex-row sm:items-start"
                >
                    <Avatar :name="employeeName(task)" size="sm" class="mt-0.5 shrink-0" />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900">{{ task.judul }}</span>
                            <span :class="statusBadge[task.status] || 'badge-neutral'">{{ task.status }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="sourceBadgeClass(task)">
                                {{ sourceLabel(task) }}
                            </span>
                            <span v-if="task.non_kpi_category" class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-medium text-violet-700">
                                {{ categoryLabels[task.non_kpi_category] ?? task.non_kpi_category }}
                            </span>
                        </div>

                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-slate-500">
                            <span class="font-medium text-slate-700">{{ employeeName(task) }}</span>
                            <span>· {{ formatDateRange(task) }}</span>
                        </div>

                        <p v-if="task.deskripsi" class="mt-1 line-clamp-2 text-xs text-slate-500 leading-relaxed">
                            {{ task.deskripsi }}
                        </p>

                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                            <!-- Review status -->
                            <span v-if="hasReview(task)" class="flex items-center gap-1 text-emerald-700">
                                <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                                Sudah direviu · Rata-rata {{ avgRating(task) }}/5
                            </span>
                            <span v-else class="flex items-center gap-1 text-amber-600">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                Belum Di Review
                            </span>

                            <!-- Evidence -->
                            <a
                                v-if="task.file_evidence_url"
                                :href="task.file_evidence_url"
                                target="_blank"
                                rel="noreferrer"
                                class="flex items-center gap-1 text-emerald-700 hover:underline"
                            >
                                <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                Lihat Evidence
                            </a>
                            <span v-else class="text-slate-400">Belum ada evidence</span>
                        </div>
                    </div>

                    <button
                        class="shrink-0 !px-3 !py-1.5 text-xs"
                        :class="hasReview(task) ? 'btn-secondary' : 'btn-primary'"
                        @click="openReview(task)"
                    >
                        {{ hasReview(task) ? 'Ubah Review' : 'Beri Review' }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Review Dialog -->
        <Dialog v-model:open="reviewDialog.open" title="Review Pekerjaan Non-KPI" class="w-full max-w-sm sm:max-w-lg">
            <template v-if="reviewDialog.task">
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ reviewDialog.task.judul }}</p>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                        <span>{{ employeeName(reviewDialog.task) }}</span>
                        <span>· {{ formatDateRange(reviewDialog.task) }}</span>
                        <span
                            v-if="reviewDialog.task.non_kpi_category"
                            class="rounded-full bg-violet-50 px-2 py-0.5 font-medium text-violet-700"
                        >
                            {{ categoryLabels[reviewDialog.task.non_kpi_category] }}
                        </span>
                    </div>
                    <!-- Evidence in dialog -->
                    <a
                        v-if="reviewDialog.task.file_evidence_url"
                        :href="reviewDialog.task.file_evidence_url"
                        target="_blank"
                        rel="noreferrer"
                        class="mt-2 flex items-center gap-1.5 text-xs text-blue-600 hover:underline"
                    >
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Lihat evidence yang diunggah karyawan
                    </a>
                </div>

                <Alert v-if="reviewDialog.error" variant="danger" class="mt-3">{{ reviewDialog.error }}</Alert>

                <div class="mt-4 space-y-4">
                    <div
                        v-for="criterion in reviewCriteria"
                        :key="criterion.key"
                        class="flex items-center justify-between gap-4"
                    >
                        <label class="w-40 shrink-0 text-sm font-medium text-slate-700">{{ criterion.label }}</label>
                        <div class="flex gap-1">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                class="transition-transform hover:scale-110 focus:outline-none"
                                @click="setRating(criterion.key, star)"
                            >
                                <svg
                                    class="h-7 w-7 transition-colors"
                                    :class="reviewForm[criterion.key] !== null && star <= reviewForm[criterion.key]
                                        ? 'text-amber-400'
                                        : 'text-slate-200'"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                >
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </button>
                        </div>
                        <span class="w-6 text-right text-sm font-semibold text-slate-700">
                            {{ reviewForm[criterion.key] ?? '—' }}
                        </span>
                    </div>

                    <div>
                        <label class="form-label">Catatan HR <span class="text-xs font-normal text-slate-400">(opsional)</span></label>
                        <textarea
                            v-model="reviewForm.review_note"
                            class="form-textarea"
                            rows="3"
                            placeholder="Keterangan tambahan tentang kinerja pegawai..."
                        />
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                    <button class="btn-secondary w-full sm:w-auto" :disabled="reviewDialog.loading" @click="reviewDialog.open = false">Batal</button>
                    <button class="btn-primary w-full sm:w-auto" :disabled="reviewDialog.loading" @click="submitReview">
                        {{ reviewDialog.loading ? 'Menyimpan...' : 'Simpan Review' }}
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
