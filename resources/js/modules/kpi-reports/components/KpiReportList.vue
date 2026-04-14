<script setup>
import Avatar from '@/components/ui/Avatar.vue';
import Button from '@/components/ui/Button.vue';
import { Eye, FileSearch, Pencil, Send, Trash2 } from 'lucide-vue-next';
import { formatDate, formatPercentage, progressTone, scoreClass, scoreLabel, statusClass, statusLabel } from '@/modules/kpi-reports/utils';

defineProps({
    reports: {
        type: Array,
        default: () => [],
    },
    showEmployee: Boolean,
    allowManage: Boolean,
    allowReview: Boolean,
});

defineEmits(['edit', 'delete', 'detail', 'evidence', 'approve', 'reject', 'submit']);
</script>

<template>
    <div class="space-y-4">
        <article
            v-for="report in reports"
            :key="report.id"
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-50"
        >
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <Avatar v-if="showEmployee" :name="report.user?.nama || '?'" size="sm" />
                        <span v-if="showEmployee" class="text-sm font-semibold text-slate-900">{{ report.user?.nama || '-' }}</span>
                        <span :class="['rounded-full border px-2.5 py-1 text-[11px] font-semibold', statusClass(report.status)]">
                            {{ statusLabel(report.status) }}
                        </span>
                        <span
                            v-if="report.score_label"
                            :class="['rounded-full border px-2.5 py-1 text-[11px] font-semibold', scoreClass(report.score_label)]"
                        >
                            {{ scoreLabel(report.score_label) }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-semibold text-slate-950">{{ report.kpi_component?.objectives || '-' }}</h3>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-500">
                                <span>{{ report.period_label || '-' }}</span>
                                <span>{{ formatDate(report.tanggal) }}</span>
                                <span>{{ report.period_type || '-' }}</span>
                                <span v-if="report.user?.jabatan">{{ report.user.jabatan }}</span>
                            </div>
                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">
                                {{ report.catatan || 'Belum ada catatan tambahan dari pegawai.' }}
                            </p>
                        </div>

                        <div :class="['grid gap-3 rounded-2xl p-4 sm:grid-cols-3 lg:min-w-[320px]', progressTone(report.persentase).surface]">
                            <div>
                                <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Target</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ report.nilai_target ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Aktual</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ report.nilai_aktual ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Capaian</p>
                                <p :class="['mt-2 text-lg font-semibold', progressTone(report.persentase).text]">
                                    {{ formatPercentage(report.persentase) }}
                                </p>
                            </div>
                            <div class="sm:col-span-3">
                                <div class="mb-2 flex items-center justify-between text-xs text-slate-500">
                                    <span>Progress KPI</span>
                                    <span>{{ formatPercentage(report.persentase) }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-white/80">
                                    <div
                                        :class="['h-2 rounded-full transition-all duration-300', progressTone(report.persentase).bar]"
                                        :style="{ width: `${Math.min(100, Number(report.persentase || 0))}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 xl:w-[220px] xl:justify-end">
                    <Button variant="outline" size="sm" @click="$emit('detail', report)">
                        <Eye class="h-4 w-4" />
                        Detail
                    </Button>
                    <Button
                        v-if="report.file_evidence_url"
                        variant="outline"
                        size="sm"
                        @click="$emit('evidence', report)"
                    >
                        <FileSearch class="h-4 w-4" />
                        Evidence
                    </Button>
                    <template v-if="allowManage && report.status === 'draft'">
                        <Button size="sm" @click="$emit('submit', report)">
                            <Send class="h-4 w-4" />
                            Submit
                        </Button>
                        <Button size="sm" @click="$emit('edit', report)">
                            <Pencil class="h-4 w-4" />
                            Edit
                        </Button>
                        <Button variant="destructive" size="sm" @click="$emit('delete', report)">
                            <Trash2 class="h-4 w-4" />
                            Hapus
                        </Button>
                    </template>
                    <template v-if="allowReview && report.status === 'submitted'">
                        <Button size="sm" @click="$emit('approve', report)">Setujui</Button>
                        <Button variant="destructive" size="sm" @click="$emit('reject', report)">Tolak</Button>
                    </template>
                </div>
            </div>
        </article>
    </div>
</template>
