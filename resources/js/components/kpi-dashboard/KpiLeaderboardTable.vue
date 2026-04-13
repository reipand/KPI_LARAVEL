<script setup>
import { computed } from 'vue';
import { ArrowUpDown, Eye } from 'lucide-vue-next';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Table from '@/components/ui/Table.vue';
import TableBody from '@/components/ui/TableBody.vue';
import TableCell from '@/components/ui/TableCell.vue';
import TableHead from '@/components/ui/TableHead.vue';
import TableHeader from '@/components/ui/TableHeader.vue';
import TableRow from '@/components/ui/TableRow.vue';
import Progress from '@/components/ui/Progress.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import KpiStatusBadge from '@/components/kpi-dashboard/KpiStatusBadge.vue';

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
    loading: Boolean,
    page: { type: Number, default: 1 },
    perPage: { type: Number, default: 8 },
    sortField: { type: String, default: 'normalized_score' },
    sortDirection: { type: String, default: 'desc' },
});

const emit = defineEmits(['sort', 'update:page', 'open-detail']);

const totalPages = computed(() => Math.max(1, Math.ceil(props.rows.length / props.perPage)));

const paginatedRows = computed(() => {
    const start = (props.page - 1) * props.perPage;

    return props.rows.slice(start, start + props.perPage);
});

function indicatorClass(score) {
    if (score >= 80) return 'bg-emerald-500';
    if (score >= 50) return 'bg-amber-500';
    return 'bg-rose-500';
}

function sortLabel(field) {
    if (props.sortField !== field) return '';

    return props.sortDirection === 'asc' ? 'asc' : 'desc';
}

function changePage(page) {
    if (page < 1 || page > totalPages.value) return;

    emit('update:page', page);
}
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-hidden rounded-[28px] border border-slate-200/70 bg-white/85 shadow-sm dark:border-slate-800 dark:bg-slate-950/75">
            <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-4 dark:border-slate-800">
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">KPI leaderboard</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        Tabel KPI dengan sorting, pencarian, dan pagination untuk kebutuhan admin panel.
                    </div>
                </div>
                <Badge variant="outline" class="rounded-full border-slate-200 bg-slate-50 px-3 py-1 dark:border-slate-800 dark:bg-slate-900">
                    {{ rows.length }} data
                </Badge>
            </div>

            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead class="w-[32%]">Nama</TableHead>
                        <TableHead class="w-[20%]">
                            <button type="button" class="inline-flex items-center gap-2" @click="$emit('sort', 'role')">
                                Role
                                <ArrowUpDown class="h-3.5 w-3.5 text-slate-400" />
                                <span class="sr-only">{{ sortLabel('role') }}</span>
                            </button>
                        </TableHead>
                        <TableHead class="w-[18%]">
                            <button type="button" class="inline-flex items-center gap-2" @click="$emit('sort', 'normalized_score')">
                                KPI Score
                                <ArrowUpDown class="h-3.5 w-3.5 text-slate-400" />
                                <span class="sr-only">{{ sortLabel('normalized_score') }}</span>
                            </button>
                        </TableHead>
                        <TableHead class="w-[18%]">Status</TableHead>
                        <TableHead class="w-[12%] text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody v-if="loading">
                    <TableRow v-for="index in perPage" :key="index" class="hover:bg-transparent">
                        <TableCell colspan="5" class="p-0">
                            <Skeleton class="mx-4 my-3 h-16 rounded-2xl" />
                        </TableCell>
                    </TableRow>
                </TableBody>

                <TableBody v-else-if="paginatedRows.length">
                    <TableRow
                        v-for="row in paginatedRows"
                        :key="row.user.id"
                        class="cursor-pointer"
                        @click="$emit('open-detail', row.user.id)"
                    >
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ row.user.nama?.slice(0, 1)?.toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-slate-900 dark:text-slate-100">{{ row.user.nama }}</div>
                                    <div class="truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ row.user.email || row.user.nip || 'Tanpa identitas tambahan' }}
                                    </div>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <div class="font-medium text-slate-700 dark:text-slate-200">{{ row.role?.name || row.user.role_ref?.name || row.user.jabatan || '-' }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ row.user.jabatan || 'Posisi belum diatur' }}</div>
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <div class="text-lg font-semibold tracking-tight text-slate-950 dark:text-white">
                                    {{ row.normalized_score }}
                                </div>
                                <Progress :value="Number(row.normalized_score)" :indicator-class="indicatorClass(Number(row.normalized_score))" />
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="space-y-2">
                                <KpiStatusBadge :score="Number(row.normalized_score)" />
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    Grade {{ row.grade }}
                                </div>
                            </div>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                variant="outline"
                                class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-slate-700 shadow-none hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                                @click.stop="$emit('open-detail', row.user.id)"
                            >
                                <Eye class="mr-2 h-4 w-4" />
                                Detail
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>

                <TableBody v-else>
                    <TableRow class="hover:bg-transparent">
                        <TableCell colspan="5" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                            Tidak ada data KPI untuk filter saat ini.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="flex items-center justify-between">
            <div class="text-sm text-slate-500 dark:text-slate-400">
                Halaman {{ page }} dari {{ totalPages }}
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                    :disabled="page <= 1"
                    @click="changePage(page - 1)"
                >
                    Sebelumnya
                </button>
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                    :disabled="page >= totalPages"
                    @click="changePage(page + 1)"
                >
                    Berikutnya
                </button>
            </div>
        </div>
    </div>
</template>
