<script setup>
import { computed, ref } from 'vue';
import { Activity, BarChart3, Target } from 'lucide-vue-next';
import Badge from '@/components/ui/Badge.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Progress from '@/components/ui/Progress.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Tabs from '@/components/ui/Tabs.vue';

const props = defineProps({
    open: Boolean,
    loading: Boolean,
    detail: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:open']);

const activeTab = ref('breakdown');

const tabs = [
    { value: 'breakdown', label: 'Breakdown' },
    { value: 'summary', label: 'Summary' },
];

const indicators = computed(() => props.detail?.breakdown ?? []);

function progressTone(score) {
    if (score >= 80) return 'bg-emerald-500';
    if (score >= 50) return 'bg-amber-500';
    return 'bg-rose-500';
}
</script>

<template>
    <Dialog
        :open="open"
        title="Detail KPI"
        description="Breakdown indikator, progress, dan target vs actual per karyawan."
        class="max-w-4xl rounded-[28px] border-slate-200 bg-white p-0 dark:border-slate-800 dark:bg-slate-950"
        @update:open="$emit('update:open', $event)"
    >
        <div class="border-b border-slate-200/70 px-6 py-5 dark:border-slate-800">
            <template v-if="detail">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-lg font-semibold text-slate-950 dark:text-white">{{ detail.user?.nama }}</div>
                        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ detail.role?.name || detail.user?.jabatan || '-' }} • {{ detail.period_start }} sampai {{ detail.period_end }}
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" class="rounded-full px-3 py-1">Score {{ detail.normalized_score }}</Badge>
                        <Badge variant="outline" class="rounded-full px-3 py-1">Grade {{ detail.grade }}</Badge>
                    </div>
                </div>
            </template>
        </div>

        <div class="px-6 py-5">
            <Tabs v-model="activeTab" :tabs="tabs">
                <template #default="{ value }">
                    <div v-if="loading" class="space-y-4">
                        <Skeleton v-for="index in 4" :key="index" class="h-24 rounded-3xl" />
                    </div>

                    <template v-else-if="detail">
                        <div v-if="value === 'summary'" class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-[24px] border border-slate-200/70 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-slate-950">
                                        <Activity class="h-4 w-4 text-slate-700 dark:text-slate-200" />
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Normalized</div>
                                        <div class="text-2xl font-semibold text-slate-950 dark:text-white">{{ detail.normalized_score }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[24px] border border-slate-200/70 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-slate-950">
                                        <BarChart3 class="h-4 w-4 text-slate-700 dark:text-slate-200" />
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Raw score</div>
                                        <div class="text-2xl font-semibold text-slate-950 dark:text-white">{{ detail.raw_score }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[24px] border border-slate-200/70 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-slate-950">
                                        <Target class="h-4 w-4 text-slate-700 dark:text-slate-200" />
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Indicators</div>
                                        <div class="text-2xl font-semibold text-slate-950 dark:text-white">{{ indicators.length }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="indicator in indicators"
                                :key="indicator.indicator_id"
                                class="rounded-[26px] border border-slate-200/70 bg-slate-50/70 p-5 transition hover:border-slate-300 hover:bg-white dark:border-slate-800 dark:bg-slate-900/70 dark:hover:border-slate-700 dark:hover:bg-slate-900"
                            >
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-2">
                                        <div class="text-base font-semibold text-slate-950 dark:text-white">{{ indicator.name }}</div>
                                        <div class="text-sm leading-6 text-slate-500 dark:text-slate-400">
                                            {{ indicator.description || 'Indikator tanpa deskripsi tambahan.' }}
                                        </div>
                                        <Badge variant="outline" class="rounded-full px-3 py-1">Weight {{ indicator.weight }}</Badge>
                                    </div>

                                    <div class="grid gap-3 text-sm text-slate-600 dark:text-slate-300 lg:min-w-[240px]">
                                        <div class="flex items-center justify-between">
                                            <span>Target</span>
                                            <span class="font-semibold text-slate-950 dark:text-white">{{ indicator.target_value }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Actual</span>
                                            <span class="font-semibold text-slate-950 dark:text-white">{{ indicator.actual_value }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Score</span>
                                            <span class="font-semibold text-slate-950 dark:text-white">{{ indicator.score }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 space-y-2">
                                    <div class="flex items-center justify-between text-xs font-medium uppercase tracking-[0.16em] text-slate-500">
                                        <span>Progress</span>
                                        <span>{{ indicator.achievement_ratio }}%</span>
                                    </div>
                                    <Progress
                                        :value="Number(indicator.achievement_ratio)"
                                        :indicator-class="progressTone(Number(indicator.achievement_ratio))"
                                        class="h-3"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </Tabs>
        </div>
    </Dialog>
</template>
