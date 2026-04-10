<script setup>
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';

defineProps({
    open: Boolean,
    isDeleting: Boolean,
    reportTitle: {
        type: String,
        default: 'laporan ini',
    },
});

defineEmits(['update:open', 'confirm']);
</script>

<template>
    <Dialog :open="open" title="Hapus laporan KPI" class="max-w-lg rounded-[28px] p-0" @update:open="$emit('update:open', $event)">
        <div class="space-y-6 p-6">
            <p class="text-sm leading-6 text-slate-600">
                <span class="font-semibold text-slate-900">{{ reportTitle }}</span> akan dihapus dari daftar secara langsung.
                Jika permintaan API gagal, data akan dikembalikan otomatis.
            </p>

            <div class="flex justify-end gap-3">
                <Button variant="outline" @click="$emit('update:open', false)">Batal</Button>
                <Button variant="destructive" :disabled="isDeleting" @click="$emit('confirm')">
                    {{ isDeleting ? 'Menghapus...' : 'Hapus laporan' }}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
