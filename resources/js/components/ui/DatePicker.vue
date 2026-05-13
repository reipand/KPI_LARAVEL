<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import 'flatpickr/dist/flatpickr.min.css';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Pilih tanggal' },
    minDate: { type: String, default: null },
    maxDate: { type: String, default: null },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);
let fp = null;

onMounted(() => {
    fp = flatpickr(inputRef.value, {
        locale: Indonesian,
        dateFormat: 'd M Y',       // tampilan: 14 Mei 2026
        altInput: false,
        allowInput: true,
        disableMobile: false,
        minDate: props.minDate || null,
        maxDate: props.maxDate || null,
        defaultDate: props.modelValue || null,
        onChange: ([date]) => {
            if (!date) { emit('update:modelValue', ''); return; }
            // emit selalu dalam format Y-m-d agar kompatibel dengan backend
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            emit('update:modelValue', `${y}-${m}-${d}`);
        },
        onReady: (_, __, instance) => {
            if (props.modelValue) instance.setDate(props.modelValue, false);
        },
    });
});

// Sync external v-model changes (e.g. when form resets)
watch(() => props.modelValue, (val) => {
    if (!fp) return;
    const current = fp.selectedDates[0];
    const currentStr = current
        ? `${current.getFullYear()}-${String(current.getMonth() + 1).padStart(2, '0')}-${String(current.getDate()).padStart(2, '0')}`
        : '';
    if (val !== currentStr) fp.setDate(val || null, false);
});

watch(() => props.minDate, (val) => fp?.set('minDate', val || null));
watch(() => props.maxDate, (val) => fp?.set('maxDate', val || null));

onUnmounted(() => fp?.destroy());
</script>

<template>
    <input
        ref="inputRef"
        type="text"
        class="form-input"
        :placeholder="placeholder"
        :disabled="disabled"
        autocomplete="off"
        readonly
    />
</template>
