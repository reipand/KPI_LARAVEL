<script setup>
import { Inbox } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Belum ada data',
    },
    description: {
        type: String,
        default: 'Data akan muncul di sini setelah tersedia.',
    },
});
</script>

<template>
    <div class="flex min-h-[320px] flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
            <Inbox class="h-6 w-6" />
        </div>
        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ title }}</h3>
        <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">{{ description }}</p>
        <slot />
    </div>
</template>
