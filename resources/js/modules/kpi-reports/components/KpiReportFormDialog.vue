<script setup>
import { computed, reactive, watch } from 'vue';
import Alert from '@/components/ui/Alert.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { useFormValidation } from '@/composables/useFormValidation';
import { periodTypeOptions } from '@/modules/kpi-reports/constants';
import { buildPeriodLabel } from '@/modules/kpi-reports/utils';

const props = defineProps({
    open: Boolean,
    report: {
        type: Object,
        default: null,
    },
    componentOptions: {
        type: Array,
        default: () => [],
    },
    components: {
        type: Array,
        default: () => [],
    },
    isSaving: Boolean,
    isUploadingEvidence: Boolean,
    errorMessage: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:open', 'submit']);

const form = reactive({
    kpi_component_id: '',
    period_type: 'monthly',
    tanggal: new Date().toISOString().slice(0, 10),
    period_label: '',
    nilai_target: '',
    nilai_aktual: '',
    catatan: '',
    evidence: null,
});

const { errors, touched, touchField, validateField, validateForm, resetValidation } = useFormValidation({
    kpi_component_id: (value) => (!value ? 'Komponen KPI wajib dipilih.' : ''),
    tanggal: (value) => (!value ? 'Tanggal wajib diisi.' : ''),
    nilai_aktual: (value) => (value === '' ? 'Nilai aktual wajib diisi.' : ''),
});

const selectedComponent = computed(() =>
    props.components.find((component) => String(component.id) === String(form.kpi_component_id))
);

const estimatedPercentage = computed(() => {
    const actual = Number(form.nilai_aktual);
    const target = Number(form.nilai_target || selectedComponent.value?.target || 0);

    if (!Number.isFinite(actual)) {
        return 0;
    }

    if (!target) {
        return actual > 0 ? 100 : 0;
    }

    return Math.round((actual / target) * 100);
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        Object.assign(form, {
            kpi_component_id: props.report ? String(props.report.kpi_component_id) : '',
            period_type: props.report?.period_type ?? 'monthly',
            tanggal: props.report?.tanggal ?? new Date().toISOString().slice(0, 10),
            period_label: props.report?.period_label ?? '',
            nilai_target: props.report?.nilai_target ?? '',
            nilai_aktual: props.report?.nilai_aktual ?? '',
            catatan: props.report?.catatan ?? '',
            evidence: null,
        });

        if (!form.period_label) {
            form.period_label = buildPeriodLabel(form.period_type, form.tanggal);
        }

        resetValidation();
    },
    { immediate: true }
);

watch(
    () => [form.period_type, form.tanggal],
    () => {
        form.period_label = buildPeriodLabel(form.period_type, form.tanggal);
    }
);

watch(
    () => form.kpi_component_id,
    () => {
        if (!form.nilai_target && selectedComponent.value?.target != null) {
            form.nilai_target = selectedComponent.value.target;
        }
    }
);

function onBlur(field) {
    touchField(field);
    validateField(field, form[field], form);
}

function onFileChange(event) {
    form.evidence = event.target.files?.[0] ?? null;
}

function handleSubmit(status = 'draft') {
    const valid = validateForm(form);

    Object.keys(touched).forEach((field) => {
        touched[field] = true;
    });

    if (!valid) {
        return;
    }

    emit('submit', {
        id: props.report?.id,
        payload: {
            kpi_component_id: Number(form.kpi_component_id),
            period_type: form.period_type,
            tanggal: form.tanggal,
            period_label: form.period_label,
            nilai_target: form.nilai_target === '' ? null : Number(form.nilai_target),
            nilai_aktual: Number(form.nilai_aktual),
            catatan: form.catatan || null,
            status,
        },
        evidence: form.evidence,
    });
}
</script>

<template>
    <Dialog
        :open="open"
        :title="report ? 'Perbarui Laporan KPI' : 'Buat Laporan KPI'"
        class="max-w-2xl rounded-[28px] p-0"
        @update:open="$emit('update:open', $event)"
    >
        <div class="space-y-6 p-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="form-label">Komponen KPI</label>
                    <Select
                        v-model="form.kpi_component_id"
                        :options="componentOptions"
                        placeholder="Pilih komponen KPI"
                        @blur="onBlur('kpi_component_id')"
                    />
                    <p v-if="touched.kpi_component_id && errors.kpi_component_id" class="mt-1 text-xs text-rose-600">{{ errors.kpi_component_id }}</p>
                </div>

                <div>
                    <label class="form-label">Periode</label>
                    <Select v-model="form.period_type" :options="periodTypeOptions" />
                </div>
                <div>
                    <label class="form-label">Tanggal</label>
                    <Input v-model="form.tanggal" type="date" @blur="onBlur('tanggal')" />
                    <p v-if="touched.tanggal && errors.tanggal" class="mt-1 text-xs text-rose-600">{{ errors.tanggal }}</p>
                </div>

                <div>
                    <label class="form-label">Nilai Target</label>
                    <Input v-model="form.nilai_target" type="number" placeholder="Otomatis dari komponen" />
                </div>
                <div>
                    <label class="form-label">Nilai Aktual</label>
                    <Input v-model="form.nilai_aktual" type="number" placeholder="Masukkan realisasi" @blur="onBlur('nilai_aktual')" />
                    <p v-if="touched.nilai_aktual && errors.nilai_aktual" class="mt-1 text-xs text-rose-600">{{ errors.nilai_aktual }}</p>
                </div>

                <div class="md:col-span-2 rounded-3xl bg-slate-50 p-4">
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Estimasi capaian</span>
                        <span class="font-semibold text-slate-900">{{ estimatedPercentage }}%</span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-white">
                        <div class="h-2 rounded-full bg-blue-600 transition-all duration-300" :style="{ width: `${Math.min(100, estimatedPercentage)}%` }" />
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Catatan</label>
                    <Textarea v-model="form.catatan" rows="4" placeholder="Tambahkan konteks hasil kerja, kendala, atau highlight penting." />
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Evidence</label>
                    <input
                        type="file"
                        accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xlsx"
                        class="block w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-500 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-xs file:font-medium file:text-white hover:file:bg-slate-700"
                        @change="onFileChange"
                    >
                </div>
            </div>

            <Alert v-if="errorMessage" variant="danger">{{ errorMessage }}</Alert>

            <div class="flex justify-end gap-3">
                <Button variant="outline" @click="$emit('update:open', false)">Batal</Button>
                <Button variant="outline" :disabled="isSaving || isUploadingEvidence" @click="handleSubmit('draft')">
                    {{ isSaving ? 'Menyimpan...' : isUploadingEvidence ? 'Mengunggah...' : report ? 'Simpan draft' : 'Simpan draft' }}
                </Button>
                <Button :disabled="isSaving || isUploadingEvidence" @click="handleSubmit('submitted')">
                    {{ isSaving ? 'Menyimpan...' : isUploadingEvidence ? 'Mengunggah...' : report ? 'Submit perubahan' : 'Submit ke HR' }}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
