<script setup>
import Button from '@/components/ui/Button.vue';

defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    lastPage: {
        type: Number,
        required: true,
    },
    from: {
        type: Number,
        default: 0,
    },
    to: {
        type: Number,
        default: 0,
    },
    total: {
        type: Number,
        default: 0,
    },
});

defineEmits(['update:page']);
</script>

<template>
    <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
            Menampilkan <span class="font-semibold text-slate-900">{{ from }}</span>
            sampai <span class="font-semibold text-slate-900">{{ to }}</span>
            dari <span class="font-semibold text-slate-900">{{ total }}</span> laporan
        </p>

        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage <= 1"
                @click="$emit('update:page', currentPage - 1)"
            >
                Sebelumnya
            </Button>
            <div class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                Halaman {{ currentPage }} / {{ lastPage }}
            </div>
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage >= lastPage"
                @click="$emit('update:page', currentPage + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
