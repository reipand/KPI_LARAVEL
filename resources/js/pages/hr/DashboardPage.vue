<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiStore } from '@/stores/kpi';
import { useTaskStore } from '@/stores/task';
import { useToast } from '@/composables/useToast';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import StatCard from '@/components/shared/StatCard.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import api from '@/services/api';

const empStore  = useEmployeeStore();
const kpiStore  = useKpiStore();
const taskStore = useTaskStore();
const toast     = useToast();
const router    = useRouter();

// ── Reactive state ────────────────────────────────────────────────────────────
const kpiComponents       = ref([]);
const unmappedTasks       = ref([]);
const loadingComponents   = ref(false);
const loadingUnmapped     = ref(false);

const mappingDialog = reactive({
    open:          false,
    task:          null,
    kpiComponentId: '',
    manualScore:   '',
    loading:       false,
    error:         '',
});

// ── Computed ──────────────────────────────────────────────────────────────────
const topRanking = computed(() => kpiStore.ranking.slice(0, 8));

const stats = computed(() => {
    const ranking = kpiStore.ranking;
    const average = ranking.length
        ? Math.round((ranking.reduce((s, i) => s + i.kpi_score, 0) / ranking.length) * 10) / 10
        : 0;
    return {
        totalEmployees: empStore.total,
        avgScore:       average,
        unmapped:       unmappedTasks.value.length,
        lowScore:       ranking.filter(i => i.kpi_score < 3).length,
    };
});

const componentOptions = computed(() =>
    kpiComponents.value.map(item => ({
        value: String(item.id),
        label: `${item.objectives} (${item.jabatan})`,
    }))
);

// ── Data loaders ──────────────────────────────────────────────────────────────
async function loadComponents() {
    loadingComponents.value = true;
    try {
        const { data: resp } = await api.get('/kpi-components', { params: { per_page: 100 } });
        kpiComponents.value = resp.data?.items ?? [];
    } finally {
        loadingComponents.value = false;
    }
}

async function loadUnmappedTasks() {
    loadingUnmapped.value = true;
    try {
        const today = new Date();
        const { data: resp } = await api.get('/tasks', {
            params: { bulan: today.getMonth() + 1, tahun: today.getFullYear(), per_page: 200 },
        });
        unmappedTasks.value = (resp.data?.items ?? []).filter(t => !t.kpi_component);
    } finally {
        loadingUnmapped.value = false;
    }
}

async function fetchAll() {
    await Promise.all([
        empStore.fetchEmployees(),
        kpiStore.fetchRanking(),
        loadComponents(),
        loadUnmappedTasks(),
    ]);
}

onMounted(fetchAll);
const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(fetchAll, { interval: 30_000 });

// ── Mapping ───────────────────────────────────────────────────────────────────
function openMapping(task) {
    mappingDialog.task          = task;
    mappingDialog.kpiComponentId = '';
    mappingDialog.manualScore   = '';
    mappingDialog.error         = '';
    mappingDialog.open          = true;
}

async function submitMapping() {
    if (!mappingDialog.kpiComponentId) {
        mappingDialog.error = 'Pilih komponen KPI terlebih dahulu.';
        return;
    }
    mappingDialog.loading = true;
    mappingDialog.error   = '';
    try {
        await taskStore.mapKpi(mappingDialog.task.id, {
            kpi_component_id: Number(mappingDialog.kpiComponentId),
            manual_score:     mappingDialog.manualScore ? Number(mappingDialog.manualScore) : null,
        });
        toast.success('Mapping KPI berhasil disimpan.');
        mappingDialog.open = false;
        await Promise.all([loadUnmappedTasks(), kpiStore.fetchRanking()]);
    } catch (err) {
        mappingDialog.error = err.response?.data?.message || 'Gagal menyimpan mapping KPI.';
    } finally {
        mappingDialog.loading = false;
    }
}

function formatDate(v) {
    if (!v) return '-';
    return new Date(v).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">HR Dashboard</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Monitoring KPI & Operasional</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                        Pantau mapping pekerjaan, ranking pegawai, dan komponen KPI aktif dari satu tampilan.
                    </p>
                </div>
                <div class="flex flex-col items-end gap-3">
                    <div class="flex items-center gap-2">
                        <span v-if="lastUpdated" class="text-[11px] text-white/50">
                            Diperbarui {{ formatTime(lastUpdated) }}
                        </span>
                        <button
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-white/70 transition hover:bg-white/20"
                            :class="{ 'animate-spin': isRefreshing }"
                            title="Refresh data"
                            @click="refresh"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                            </svg>
                        </button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[360px]">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Total Pegawai</div>
                            <div class="mt-2 text-3xl font-bold text-white">{{ stats.totalEmployees }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Rata-rata KPI</div>
                            <div class="mt-2 text-3xl font-bold text-white">{{ stats.avgScore }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <StatCard label="Total Pegawai" :value="stats.totalEmployees" />
            <StatCard label="Rata-rata KPI" :value="stats.avgScore" :color="stats.avgScore >= 4 ? 'success' : stats.avgScore >= 3 ? 'warning' : 'danger'" />
            <StatCard label="Belum Di-map" :value="stats.unmapped" :color="stats.unmapped > 0 ? 'warning' : 'success'" />
            <StatCard label="Nilai Rendah" :value="stats.lowScore" :color="stats.lowScore > 0 ? 'danger' : 'default'" />
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.2fr_0.8fr]">
            <!-- Unmapped tasks -->
            <section class="dashboard-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="section-heading">Prioritas HR</p>
                        <h3 class="mt-1 text-xl font-bold text-slate-900">Tugas belum dihubungkan ke KPI</h3>
                        <p class="mt-1 text-sm text-slate-500">Mapping agar scoring dan ranking tetap akurat.</p>
                    </div>
                    <button class="btn-secondary text-xs" @click="router.push('/hr/mapping')">Lihat semua</button>
                </div>
                <div class="p-6">
                    <template v-if="loadingUnmapped">
                        <div class="space-y-3"><Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" /></div>
                    </template>
                    <template v-else-if="unmappedTasks.length">
                        <div class="space-y-3">
                            <div v-for="task in unmappedTasks.slice(0,8)" :key="task.id" class="data-row">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <Avatar :name="task.user?.nama || '?'" size="sm" />
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-slate-900">{{ task.judul }}</div>
                                        <div class="mt-0.5 text-xs text-slate-500">{{ task.user?.nama || '-' }} · {{ formatDate(task.tanggal) }}</div>
                                    </div>
                                </div>
                                <button class="btn-primary !px-3 !py-1.5 text-xs shrink-0" @click="openMapping(task)">Map KPI</button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="py-12 text-center text-sm text-slate-400">
                        Semua pekerjaan bulan ini sudah di-map.
                    </div>
                </div>
            </section>

            <!-- KPI Components -->
            <section class="dashboard-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="section-heading">Master KPI</p>
                        <h3 class="mt-1 text-xl font-bold text-slate-900">Komponen aktif</h3>
                    </div>
                    <button class="btn-secondary text-xs" @click="router.push('/hr/kpi-components')">Kelola</button>
                </div>
                <div class="p-6">
                    <template v-if="loadingComponents">
                        <div class="space-y-3"><Skeleton v-for="i in 4" :key="i" class="h-14 rounded-2xl" /></div>
                    </template>
                    <template v-else-if="kpiComponents.length">
                        <div class="space-y-3">
                            <div v-for="item in kpiComponents.slice(0, 6)" :key="item.id" class="data-row">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ item.objectives }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">{{ item.jabatan }} · {{ item.tipe }} · Bobot {{ item.bobot }}</div>
                                </div>
                                <span :class="item.is_active ? 'badge-success' : 'badge-neutral'">{{ item.is_active ? 'Aktif' : '-' }}</span>
                            </div>
                        </div>
                    </template>
                    <div v-else class="py-10 text-center text-sm text-slate-400">Belum ada komponen KPI.</div>
                </div>
            </section>
        </div>

        <!-- Ranking -->
        <section class="dashboard-panel mt-5 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="section-heading">Peringkat</p>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">Ranking KPI seluruh pegawai</h3>
                </div>
                <button class="btn-secondary text-xs" @click="router.push('/hr/laporan-review')">Tinjau Laporan</button>
            </div>
            <div class="p-6">
                <template v-if="kpiStore.isLoading">
                    <div class="space-y-3"><Skeleton v-for="i in 8" :key="i" class="h-14 rounded-2xl" /></div>
                </template>
                <template v-else-if="topRanking.length">
                    <div class="space-y-3">
                        <div v-for="item in topRanking" :key="item.user_id" class="data-row">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold',
                                    item.rank === 1 ? 'bg-amber-400 text-white' :
                                    item.rank === 2 ? 'bg-slate-400 text-white' :
                                    item.rank === 3 ? 'bg-orange-400 text-white' : 'bg-slate-100 text-slate-700']">
                                    #{{ item.rank }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">{{ item.position }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-base font-bold text-slate-900">{{ item.kpi_score }}</div>
                                <div class="text-[11px] text-slate-500">nilai KPI</div>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="py-12 text-center text-sm text-slate-400">Data ranking belum tersedia.</div>
            </div>
        </section>

        <!-- Mapping Dialog -->
        <Dialog v-model:open="mappingDialog.open" title="Mapping Pekerjaan ke KPI" class="max-w-lg">
            <template v-if="mappingDialog.task">
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                    <p class="font-semibold text-slate-900">{{ mappingDialog.task.judul }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ mappingDialog.task.user?.nama || '-' }} · {{ formatDate(mappingDialog.task.tanggal) }}</p>
                </div>

                <Alert v-if="mappingDialog.error" variant="danger" class="mt-3">{{ mappingDialog.error }}</Alert>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="form-label">Komponen KPI <span class="text-red-500">*</span></label>
                        <select v-model="mappingDialog.kpiComponentId" class="form-input">
                            <option value="">— Pilih Komponen KPI —</option>
                            <option v-for="opt in componentOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Skor Manual <span class="text-slate-400 text-xs">(opsional, 0–5)</span></label>
                        <input v-model="mappingDialog.manualScore" type="number" min="0" max="5" step="0.01" class="form-input" placeholder="Biarkan kosong untuk auto-hitung" />
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button class="btn-secondary" :disabled="mappingDialog.loading" @click="mappingDialog.open = false">Batal</button>
                    <button class="btn-primary" :disabled="mappingDialog.loading" @click="submitMapping">
                        {{ mappingDialog.loading ? 'Menyimpan...' : 'Simpan Mapping' }}
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
