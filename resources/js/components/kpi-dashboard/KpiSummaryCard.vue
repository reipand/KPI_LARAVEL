<script setup>
import { computed } from 'vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    description: { type: String, default: '' },
    icon: { type: Object, required: true },
    tone: { type: String, default: 'default' },
    chip: { type: String, default: '' },
});

const toneClasses = computed(() => ({
    default: 'from-slate-50 via-white to-slate-100 text-slate-700 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 dark:text-slate-200',
    success: 'from-emerald-50 via-white to-emerald-100 text-emerald-700 dark:from-emerald-950/60 dark:via-slate-950 dark:to-emerald-950/40 dark:text-emerald-300',
    warning: 'from-amber-50 via-white to-amber-100 text-amber-700 dark:from-amber-950/60 dark:via-slate-950 dark:to-amber-950/40 dark:text-amber-300',
    danger: 'from-rose-50 via-white to-rose-100 text-rose-700 dark:from-rose-950/60 dark:via-slate-950 dark:to-rose-950/40 dark:text-rose-300',
    info: 'from-blue-50 via-white to-cyan-100 text-blue-700 dark:from-blue-950/60 dark:via-slate-950 dark:to-cyan-950/40 dark:text-blue-300',
}[props.tone] ?? ''));
</script>

<template>
    <Card class="rounded-[24px] border-slate-200/70 bg-white/90 p-0 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-950/80">
        <div class="relative overflow-hidden rounded-[24px]">
            <div :class="cn('absolute inset-0 bg-gradient-to-br opacity-90', toneClasses)" />
            <div class="relative flex items-start justify-between gap-4 p-5">
                <div class="space-y-3">
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                        {{ title }}
                    </div>
                    <div class="text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        {{ value }}
                    </div>
                    <p class="max-w-[20rem] text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ description }}
                    </p>
                </div>

                <div class="flex flex-col items-end gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/80 shadow-sm ring-1 ring-white/70 dark:bg-slate-900/70 dark:ring-slate-800">
                        <component :is="icon" class="h-5 w-5" />
                    </div>
                    <Badge v-if="chip" variant="outline" class="border-white/70 bg-white/60 text-[11px] dark:border-slate-700 dark:bg-slate-900/70">
                        {{ chip }}
                    </Badge>
                </div>
            </div>
        </div>
    </Card>
</template>
