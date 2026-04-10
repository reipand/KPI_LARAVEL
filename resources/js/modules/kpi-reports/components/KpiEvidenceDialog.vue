<script setup>
import Dialog from '@/components/ui/Dialog.vue';

defineProps({
    open: Boolean,
    report: {
        type: Object,
        default: null,
    },
});

defineEmits(['update:open']);
</script>

<template>
    <Dialog :open="open" title="Evidence laporan" class="max-w-4xl rounded-[28px] p-0" @update:open="$emit('update:open', $event)">
        <div class="space-y-4 p-6">
            <div class="overflow-hidden rounded-3xl border border-slate-200">
                <img
                    v-if="/\.(png|jpe?g)$/i.test(report?.file_evidence_url || '')"
                    :src="report?.file_evidence_url"
                    :alt="report?.kpi_component?.objectives || 'Evidence'"
                    class="max-h-[70vh] w-full object-contain"
                >
                <iframe
                    v-else-if="/\.pdf$/i.test(report?.file_evidence_url || '')"
                    :src="report?.file_evidence_url"
                    title="Evidence PDF"
                    class="h-[70vh] w-full"
                />
                <div v-else class="flex min-h-[320px] items-center justify-center bg-slate-50">
                    <a :href="report?.file_evidence_url" target="_blank" rel="noreferrer" class="btn-primary">Unduh evidence</a>
                </div>
            </div>

            <div class="flex justify-end">
                <a
                    :href="report?.file_evidence_url"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex items-center justify-center rounded-md border border-slate-200 px-4 py-2 text-sm font-medium text-slate-900 transition-all duration-200 hover:bg-slate-50 active:scale-95"
                >
                    Buka di tab baru
                </a>
            </div>
        </div>
    </Dialog>
</template>
