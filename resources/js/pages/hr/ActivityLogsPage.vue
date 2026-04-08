<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import AppLayout from '@/components/layout/AppLayout.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import api from '@/services/api';

// ── State ─────────────────────────────────────────────────────────────────────
const logs        = ref([]);
const meta        = ref(null);
const isLoading   = ref(false);
const currentPage = ref(1);

// Filters
const filterAction  = ref('');
const filterUserId  = ref('');
const perPage       = ref(20);

// ── Fetch ──────────────────────────────────────────────────────────────────────
async function fetchLogs(page = currentPage.value) {
    isLoading.value = true;
    try {
        const params = { page, per_page: perPage.value };
        if (filterAction.value)  params.action  = filterAction.value;
        if (filterUserId.value)  params.user_id = filterUserId.value;

        const { data: resp } = await api.get('/logs', { params });
        logs.value        = resp.data ?? [];
        meta.value        = resp.meta ?? null;
        currentPage.value = page;
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => fetchLogs(1));

watch([filterAction, filterUserId, perPage], () => fetchLogs(1));

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(() => fetchLogs(currentPage.value), { interval: 30_000 });

// ── Helpers ────────────────────────────────────────────────────────────────────
function formatDate(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
}

const actionColorMap = {
    create:  'bg-green-100 text-green-700',
    update:  'bg-blue-100 text-blue-700',
    delete:  'bg-red-100 text-red-700',
    login:   'bg-purple-100 text-purple-700',
    logout:  'bg-slate-100 text-slate-600',
    approve: 'bg-emerald-100 text-emerald-700',
    reject:  'bg-orange-100 text-orange-700',
};

function actionColor(action) {
    const key = Object.keys(actionColorMap).find(k => action?.toLowerCase().includes(k));
    return key ? actionColorMap[key] : 'bg-slate-100 text-slate-600';
}

function modelLabel(type) {
    if (!type) return '-';
    return type.split('\\').pop();
}

const uniqueActions = computed(() => {
    const set = new Set(logs.value.map(l => l.action).filter(Boolean));
    return [...set].sort();
});

const totalPages = computed(() => meta.value?.last_page ?? 1);
const from       = computed(() => meta.value?.from ?? 0);
const to         = computed(() => meta.value?.to ?? 0);
const total      = computed(() => meta.value?.total ?? 0);

function goPage(p) {
    if (p < 1 || p > totalPages.value) return;
    fetchLogs(p);
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">HR Manager</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Log Aktivitas</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Rekam jejak aktivitas sistem — login, perubahan data, dan tindakan penting lainnya.
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

        <!-- Filters -->
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <select v-model="filterAction" class="form-input !w-auto min-w-[160px]">
                <option value="">Semua Aksi</option>
                <option v-for="a in uniqueActions" :key="a" :value="a">{{ a }}</option>
            </select>

            <select v-model="perPage" class="form-input !w-auto">
                <option :value="20">20 per halaman</option>
                <option :value="50">50 per halaman</option>
                <option :value="100">100 per halaman</option>
            </select>

            <div v-if="total > 0" class="ml-auto text-xs text-slate-400">
                Menampilkan {{ from }}–{{ to }} dari {{ total }} entri
            </div>
        </div>

        <!-- Table panel -->
        <div class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Audit Trail</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">Riwayat Aktivitas Sistem</h3>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="space-y-2 p-6">
                <Skeleton v-for="i in 8" :key="i" class="h-12 rounded-lg" />
            </div>

            <!-- Empty -->
            <div v-else-if="!logs.length" class="py-20 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">📋</div>
                <p class="text-sm font-medium text-slate-500">Tidak ada log aktivitas</p>
                <p class="mt-1 text-xs text-slate-400">Log akan muncul ketika ada aktivitas sistem</p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 text-left">Waktu</th>
                            <th class="px-6 py-3 text-left">Pengguna</th>
                            <th class="px-6 py-3 text-left">Aksi</th>
                            <th class="px-6 py-3 text-left">Model</th>
                            <th class="px-6 py-3 text-left">ID</th>
                            <th class="px-6 py-3 text-left">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="log in logs"
                            :key="log.id"
                            class="transition-colors hover:bg-slate-50"
                        >
                            <td class="whitespace-nowrap px-6 py-3 text-xs text-slate-500">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-6 py-3">
                                <div v-if="log.user">
                                    <p class="font-medium text-slate-800">{{ log.user.nama }}</p>
                                    <p class="text-[11px] text-slate-400">{{ log.user.role }}</p>
                                </div>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-6 py-3">
                                <span
                                    :class="['inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold', actionColor(log.action)]"
                                >
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-xs text-slate-600">
                                {{ modelLabel(log.model_type) }}
                            </td>
                            <td class="px-6 py-3 text-xs text-slate-500">
                                {{ log.model_id ?? '—' }}
                            </td>
                            <td class="px-6 py-3 font-mono text-xs text-slate-400">
                                {{ log.ip_address ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-6 py-3">
                <button
                    class="btn-secondary text-xs"
                    :disabled="currentPage <= 1"
                    @click="goPage(currentPage - 1)"
                >
                    ← Sebelumnya
                </button>
                <div class="flex items-center gap-1">
                    <template v-for="p in totalPages" :key="p">
                        <button
                            v-if="p === 1 || p === totalPages || Math.abs(p - currentPage) <= 2"
                            :class="[
                                'flex h-7 w-7 items-center justify-center rounded-md text-xs font-medium transition',
                                p === currentPage
                                    ? 'bg-blue-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100',
                            ]"
                            @click="goPage(p)"
                        >{{ p }}</button>
                        <span
                            v-else-if="p === 2 && currentPage > 4"
                            class="px-1 text-slate-400"
                        >…</span>
                        <span
                            v-else-if="p === totalPages - 1 && currentPage < totalPages - 3"
                            class="px-1 text-slate-400"
                        >…</span>
                    </template>
                </div>
                <button
                    class="btn-secondary text-xs"
                    :disabled="currentPage >= totalPages"
                    @click="goPage(currentPage + 1)"
                >
                    Berikutnya →
                </button>
            </div>
        </div>
    </AppLayout>
</template>
