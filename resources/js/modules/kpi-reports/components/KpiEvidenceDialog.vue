<script setup>
import Dialog from '@/components/ui/Dialog.vue';
import { computed } from 'vue';

const props = defineProps({
    open: Boolean,
    report: {
        type: Object,
        default: null,
    },
});

defineEmits(['update:open']);

const evidenceUrl = computed(() => props.report?.file_evidence_url || '');

const evidenceExtension = computed(() => {
    const source = props.report?.file_evidence || evidenceUrl.value;

    if (!source) {
        return '';
    }

    const sanitized = source.split('?')[0].toLowerCase();
    const extension = sanitized.split('.').pop();

    return extension || '';
});

const isImageEvidence = computed(() => ['png', 'jpg', 'jpeg', 'webp'].includes(evidenceExtension.value));
const isPdfEvidence = computed(() => evidenceExtension.value === 'pdf');
</script>

<template>
    <Dialog :open="open" title="Evidence laporan" class="max-w-4xl rounded-[28px] p-0" @update:open="$emit('update:open', $event)">
        <div class="space-y-4 p-6">
            <div class="overflow-hidden rounded-3xl border border-slate-200">
                <img
                    v-if="isImageEvidence && evidenceUrl"
                    :src="evidenceUrl"
                    :alt="report?.kpi_component?.objectives || 'Evidence'"
                    class="max-h-[70vh] w-full object-contain"
                >
                <iframe
                    v-else-if="isPdfEvidence && evidenceUrl"
                    :src="evidenceUrl"
                    title="Evidence PDF"
                    class="h-[70vh] w-full"
                />
                <div v-else-if="evidenceUrl" class="flex min-h-[320px] items-center justify-center bg-slate-50">
                    <a :href="evidenceUrl" target="_blank" rel="noreferrer" class="btn-primary">Unduh evidence</a>
                </div>
                <div v-else class="flex min-h-[320px] items-center justify-center bg-slate-50 px-6 text-center text-sm text-slate-500">
                    File evidence belum tersedia untuk laporan ini.
                </div>
            </div>

            <div class="flex justify-end" v-if="evidenceUrl">
                <a
                    :href="evidenceUrl"
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
