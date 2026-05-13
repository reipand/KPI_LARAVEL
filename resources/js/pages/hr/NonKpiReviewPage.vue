<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import { useNonKpiReviewStore } from '@/stores/nonKpiReview';
import { useToast } from '@/composables/useToast';

const store = useNonKpiReviewStore();
const toast = useToast();

const filterBulan    = ref(new Date().getMonth() + 1);
const filterTahun    = ref(new Date().getFullYear());
const filterCategory = ref('');
const filterStatus   = ref('');

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

const categoryOptions = [
    { value: 'cross_division',      label: 'Bantuan Lintas Divisi' },
    { value: 'incidental',          label: 'Tugas Insidental' },
    { value: 'operational_support', label: 'Support Operasional' },
    { value: 'problem_solving',     label: 'Problem Solving' },
    { value: 'administration',      label: 'Administrasi Tambahan' },
];
const categoryLabels = Object.fromEntries(categoryOptions.map((o) => [o.value, o.label]));

const statusOptions = [
    { value: 'Selesai',      label: 'Selesai' },
    { value: 'Dalam Proses', label: 'Dalam Proses' },
    { value: 'Pending',      label: 'Pending' },
];

const reviewCriteria = [
    { key: 'review_quality',      label: 'Kualitas' },
    { key: 'review_timeliness',   label: 'Ketepatan Waktu' },
    { key: 'review_initiative',   label: 'Inisiatif' },
    { key: 'review_contribution', label: 'Kontribusi' },
];

function buildParams() {
    return {
        bulan:            filterBulan.value,
        tahun:            filterTahun.value,
        non_kpi_category: filterCategory.value || undefined,
        status:           filterStatus.value   || undefined,
        per_page:         50,
    };
}

function loadTasks() {
    store.fetchTasks(buildParams());
}

onMounted(loadTasks);
watch([filterBulan, filterTahun, filterCategory, filterStatus], loadTasks);

// ── Review dialog ─────────────────────────────────────────────────────────────
const dialog = reactive({
    open:    false,
    task:    null,
    loading: false,
    error:   '',
});
const reviewForm = reactive({
    review_quality:      null,
    review_timeliness:   null,
    review_initiative:   null,
    review_contribution: null,
    review_note:         '',
});

function openReview(task) {
    dialog.task    = task;
    dialog.error   = '';
    reviewForm.review_quality      = task.review_quality      ?? null;
    reviewForm.review_timeliness   = task.review_timeliness   ?? null;
    reviewForm.review_initiative   = task.review_initiative   ?? null;
    reviewForm.review_contribution = task.review_contribution ?? null;
    reviewForm.review_note         = task.review_note         ?? '';
    dialog.open    = true;
}

function setRating(field, value) {
    reviewForm[field] = reviewForm[field] === value ? null : value;
}

async function submitReview() {
    dialog.loading = true;
    dialog.error   = '';
    try {
        await store.submitReview(dialog.task.id, {
            review_quality:      reviewForm.review_quality,
            review_timeliness:   reviewForm.review_timeliness,
            review_initiative:   reviewForm.review_initiative,
            review_contribution: reviewForm.review_contribution,
            review_note:         reviewForm.review_note || null,
        });
        toast.success('Review berhasil disimpan.');
        dialog.open = false;
    } catch (err) {
        dialog.error = err.response?.data?.message || 'Gagal menyimpan review.';
    } finally {
        dialog.loading = false;
    }
}

function avgRating(task) {
    const vals = [
        task.review_quality,
        task.review_timeliness,
        task.review_initiative,
        task.review_contribution,
    ].filter((v) => v !== null && v !== undefined);
    if (!vals.length) return null;
    return (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1);
}

function hasReview(task) {
    return task.review_quality !== null && task.review_quality !== undefined;
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

const reviewedCount  = computed(() => store.tasks.filter(hasReview).length);
const pendingCount   = computed(() => store.tasks.filter((t) => !hasReview(t)).length);
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">HR Panel · Evaluasi Non-KPI</div>
                    <h2 class="mt-4 text-2xl font-bold md:text-3xl">Review Pekerjaan Non-KPI</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-white/78">
                        Evaluasi kontribusi pegawai dari pekerjaan operasional dan lintas fungsi yang tidak masuk KPI formal.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:min-w-[280px]">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Belum Direviu</div>
                        <div class="mt-2 text-2xl font-bold" :class="pendingCount > 0 ? 'text-amber-300' : 'text-white'">
                            {{ pendingCount }}
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Sudah Direviu</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ reviewedCount }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <select v-model="filterBulan" class="form-input !w-auto">
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <select v-model="filterTahun" class="form-input !w-auto">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <select v-model="filterCategory" class="form-input !w-auto">
                <option value="">Semua Kategori</option>
                <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <select v-model="filterStatus" class="form-input !w-auto">
                <option value="">Semua Status</option>
                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
        </div>

        <!-- Task list -->
        <section class="dashboard-panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-base font-bold text-slate-900">
                    Pekerjaan Non-KPI
                    <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                        {{ store.pagination.total }}
                    </span>
                </h3>
            </div>

            <template v-if="store.isLoading">
                <div class="space-y-3 p-6">
                    <Skeleton v-for="i in 6" :key="i" class="h-16 rounded-2xl" />
                </div>
            </template>

            <div v-else-if="!store.tasks.length" class="py-16 text-center text-sm text-slate-400">
                Tidak ada pekerjaan Non-KPI untuk periode ini.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="task in store.tasks"
                    :key="task.id"
                    class="flex flex-col gap-3 px-6 py-4 transition-colors hover:bg-slate-50 sm:flex-row sm:items-center"
                >
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <Avatar :name="(task.user?.nama || task.assignee?.nama || '?')" size="sm" class="mt-0.5 shrink-0" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ task.judul }}</span>
                                <span :class="statusBadge[task.status] || 'badge-neutral'" class="shrink-0">{{ task.status }}</span>
                                <span v-if="task.non_kpi_category" class="rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700">
                                    {{ categoryLabels[task.non_kpi_category] ?? task.non_kpi_category }}
                                </span>
                            </div>
                            <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                <span>{{ task.user?.nama || task.assignee?.nama || '-' }}</span>
                                <span>· {{ formatDateRange(task) }}</span>
                            </div>
                            <!-- Review summary -->
                            <div v-if="hasReview(task)" class="mt-1.5 flex items-center gap-1.5 text-xs text-emerald-700">
                                <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                                <span>Sudah direviu · Rata-rata {{ avgRating(task) }}/5</span>
                            </div>
                            <div v-else class="mt-1.5 flex items-center gap-1.5 text-xs text-amber-600">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>
                                </svg>
                                Belum Di Review
                            </div>
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
        <Dialog v-model:open="dialog.open" title="Review Pekerjaan Non-KPI" class="w-full max-w-sm sm:max-w-lg">
            <template v-if="dialog.task">
                <!-- Task info -->
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ dialog.task.judul }}</p>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                        <span>{{ dialog.task.user?.nama || dialog.task.assignee?.nama || '-' }}</span>
                        <span>· {{ formatDateRange(dialog.task) }}</span>
                        <span
                            v-if="dialog.task.non_kpi_category"
                            class="rounded-full bg-violet-50 px-2 py-0.5 font-medium text-violet-700"
                        >
                            {{ categoryLabels[dialog.task.non_kpi_category] ?? dialog.task.non_kpi_category }}
                        </span>
                    </div>
                </div>

                <Alert v-if="dialog.error" variant="danger" class="mt-3">{{ dialog.error }}</Alert>

                <div class="mt-4 space-y-4">
                    <!-- Star ratings -->
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

                    <!-- Note -->
                    <div>
                        <label class="form-label">Catatan HR <span class="text-xs font-normal text-slate-400">(opsional)</span></label>
                        <textarea
                            v-model="reviewForm.review_note"
                            class="form-textarea"
                            rows="3"
                            placeholder="Keterangan tambahan tentang kinerja pegawai pada pekerjaan ini..."
                        />
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                    <button class="btn-secondary w-full sm:w-auto" :disabled="dialog.loading" @click="dialog.open = false">Batal</button>
                    <button class="btn-primary w-full sm:w-auto" :disabled="dialog.loading" @click="submitReview">
                        {{ dialog.loading ? 'Menyimpan...' : 'Simpan Review' }}
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
