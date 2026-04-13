<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    indicatorClass: { type: String, default: '' },
    class: { type: String, default: '' },
});

const width = computed(() => {
    if (props.max <= 0) return 0;

    return Math.max(0, Math.min(100, (props.value / props.max) * 100));
});
</script>

<template>
    <div
        :class="cn(
            'h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800',
            $props.class
        )"
    >
        <div
            :class="cn(
                'h-full rounded-full bg-emerald-500 transition-all duration-500 ease-out',
                indicatorClass
            )"
            :style="{ width: `${width}%` }"
        />
    </div>
</template>
