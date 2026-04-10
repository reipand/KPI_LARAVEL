<script setup>
defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="item in items"
            :key="item.label"
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-50"
        >
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ item.label }}</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ item.value }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ item.hint }}</p>
        </article>
    </section>
</template>
