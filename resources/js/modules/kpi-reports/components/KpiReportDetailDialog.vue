<script setup>
import Avatar from '@/components/ui/Avatar.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import { formatDate, formatDateTime, formatPercentage, progressTone, scoreClass, scoreLabel, statusClass, statusLabel } from '@/modules/kpi-reports/utils';
import { computed } from 'vue';

const props = defineProps({
    open: Boolean,
    report: { type: Object, default: null },
    showEmployee: Boolean,
});

defineEmits(['update:open', 'evidence']);

const tone = computed(() => progressTone(props.report?.persentase));
const pct = computed(() => {
    const v = Number(props.report?.persentase);
    return Number.isFinite(v) ? Math.min(v, 100) : 0;
});
</script>

<template>
    <Dialog :open="open" title="Detail Laporan KPI" class="max-w-2xl rounded-3xl p-0" @update:open="$emit('update:open', $event)">
        <div v-if="report" class="divide-y divide-slate-100">

            <!-- ── Header ── -->
            <div class="flex items-start gap-4 p-6">
                <Avatar v-if="showEmployee" :name="report.user?.nama || '?'" size="md" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="['inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold', statusClass(report.status)]">
                            {{ statusLabel(report.status) }}
                        </span>
                        <span v-if="report.score_label" :class="['inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold', scoreClass(report.score_label)]">
                            {{ scoreLabel(report.score_label) }}
                        </span>
                    </div>
                    <h3 class="mt-2 text-lg font-bold leading-snug text-slate-900">
                        {{ report.kpi_indicator?.name || '-' }}
                    </h3>
                    <div v-if="showEmployee" class="mt-1 text-sm font-medium text-slate-700">{{ report.user?.nama }}</div>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ report.user?.jabatan || 'Pegawai' }}
                        <span class="mx-1">·</span>{{ report.period_label || '-' }}
                        <span class="mx-1">·</span>{{ report.period_type || '-' }}
                    </p>
                </div>
            </div>

            <!-- ── Progress ── -->
            <div class="px-6 py-5">
                <div class="flex items-end justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Pencapaian</span>
                    <span :class="['text-2xl font-bold tabular-nums', tone.text]">{{ formatPercentage(report.persentase) }}</span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        :class="['h-full rounded-full transition-all duration-700', tone.bar]"
                        :style="{ width: pct + '%' }"
                    />
                </div>
                <div class="mt-4 grid grid-cols-3 divide-x divide-slate-100 rounded-2xl border border-slate-100 bg-slate-50">
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] uppercase tracking-widest text-slate-400">Target</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ report.nilai_target ?? '-' }}</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] uppercase tracking-widest text-slate-400">Aktual</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ report.nilai_aktual ?? '-' }}</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] uppercase tracking-widest text-slate-400">Tanggal</p>
                        <p class="mt-1 text-base font-bold text-slate-900">{{ formatDate(report.tanggal) }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Catatan ── -->
            <div class="grid grid-cols-1 gap-0 divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                <div class="px-6 py-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Catatan Pegawai</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        {{ report.catatan || 'Belum ada catatan tambahan.' }}
                    </p>
                </div>
                <div class="px-6 py-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Catatan Reviewer</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        {{ report.review_note || 'Belum ada catatan reviewer.' }}
                    </p>
                </div>
            </div>

            <!-- ── Footer: timeline + evidence ── -->
            <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                <div class="space-y-1 text-xs text-slate-400">
                    <p><span class="font-medium text-slate-600">Dibuat</span> · {{ formatDateTime(report.created_at) }}</p>
                    <p><span class="font-medium text-slate-600">Diajukan</span> · {{ formatDateTime(report.submitted_at) || '-' }}</p>
                    <p><span class="font-medium text-slate-600">Direview</span> · {{ formatDateTime(report.reviewed_at) || '-' }}</p>
                </div>
                <div>
                    <Button v-if="report.file_evidence_url" size="sm" variant="outline" @click="$emit('evidence', report)">
                        Lihat Evidence
                    </Button>
                    <span v-else class="text-xs text-slate-400">Tidak ada evidence</span>
                </div>
            </div>

        </div>
    </Dialog>
</template>
