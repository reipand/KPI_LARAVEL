<script setup>
import { reactive, watch } from 'vue';
import Alert from '@/components/ui/Alert.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Textarea from '@/components/ui/Textarea.vue';

const props = defineProps({
    open: Boolean,
    action: {
        type: String,
        default: 'approved',
    },
    report: {
        type: Object,
        default: null,
    },
    isSubmitting: Boolean,
});

const emit = defineEmits(['update:open', 'submit']);

const state = reactive({
    review_note: '',
    error: '',
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        state.review_note = props.report?.review_note ?? '';
        state.error = '';
    },
    { immediate: true }
);

function handleSubmit() {
    if (props.action === 'rejected' && !state.review_note.trim()) {
        state.error = 'Catatan reviewer wajib diisi saat menolak laporan.';
        return;
    }

    emit('submit', {
        id: props.report?.id,
        payload: {
            status: props.action,
            review_note: state.review_note.trim() || null,
        },
    });
}
</script>

<template>
    <Dialog
        :open="open"
        :title="action === 'approved' ? 'Setujui laporan' : 'Tolak laporan'"
        class="max-w-2xl rounded-[28px] p-0"
        @update:open="$emit('update:open', $event)"
    >
        <div class="space-y-6 p-6">
            <div class="rounded-3xl bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Komponen KPI</p>
                <p class="mt-2 text-base font-semibold text-slate-950">{{ report?.kpi_component?.objectives || '-' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ report?.user?.nama || '-' }} | {{ report?.period_label || '-' }}</p>
            </div>

            <div>
                <label class="form-label">Catatan reviewer</label>
                <Textarea
                    v-model="state.review_note"
                    rows="4"
                    :placeholder="action === 'approved'
                        ? 'Tambahkan arahan singkat untuk pegawai jika diperlukan.'
                        : 'Jelaskan alasan penolakan dan apa yang harus diperbaiki.'"
                />
            </div>

            <Alert v-if="state.error" variant="danger">{{ state.error }}</Alert>

            <div class="flex justify-end gap-3">
                <Button variant="outline" @click="$emit('update:open', false)">Batal</Button>
                <Button :variant="action === 'approved' ? 'default' : 'destructive'" :disabled="isSubmitting" @click="handleSubmit">
                    {{ isSubmitting ? 'Memproses...' : action === 'approved' ? 'Setujui laporan' : 'Tolak laporan' }}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
