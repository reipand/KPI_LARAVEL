<script setup>
import Skeleton from '@/components/ui/Skeleton.vue';

defineProps({
    count: {
        type: Number,
        default: 6,
    },
});
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="index in count"
            :key="index"
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1 space-y-3">
                    <Skeleton class="h-4 w-40 rounded-full" />
                    <Skeleton class="h-5 w-64 rounded-full" />
                    <Skeleton class="h-4 w-full max-w-xl rounded-full" />
                    <Skeleton class="h-2 w-full rounded-full" />
                </div>
                <div class="grid w-full gap-3 sm:grid-cols-2 lg:w-[260px]">
                    <Skeleton class="h-20 rounded-2xl" />
                    <Skeleton class="h-20 rounded-2xl" />
                </div>
            </div>
        </div>
    </div>
</template>
