<script setup>
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';

const props = defineProps({
    score: { type: Number, default: 0 },
});

const state = computed(() => {
    if (props.score >= 80) {
        return { label: 'Good', variant: 'success' };
    }

    if (props.score >= 50) {
        return { label: 'Average', variant: 'warning' };
    }

    return { label: 'Bad', variant: 'danger' };
});
</script>

<template>
    <Badge :variant="state.variant" class="rounded-full px-2.5 py-1 text-[11px] font-semibold">
        {{ state.label }}
    </Badge>
</template>
