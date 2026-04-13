<script setup>
import { computed } from 'vue';
import { Filter, Search, SlidersHorizontal } from 'lucide-vue-next';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    roleOptions: {
        type: Array,
        default: () => [],
    },
    employeeOptions: {
        type: Array,
        default: () => [],
    },
    search: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'update:search', 'apply']);

const monthValue = computed({
    get: () => props.modelValue.period,
    set: (value) => emit('update:modelValue', { ...props.modelValue, period: value }),
});

const roleValue = computed({
    get: () => props.modelValue.roleId,
    set: (value) => emit('update:modelValue', { ...props.modelValue, roleId: value, employeeId: '' }),
});

const employeeValue = computed({
    get: () => props.modelValue.employeeId,
    set: (value) => emit('update:modelValue', { ...props.modelValue, employeeId: value }),
});
</script>

<template>
    <Card class="rounded-[28px] border-slate-200/70 bg-white/85 p-4 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/75">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-white dark:text-slate-900">
                    <SlidersHorizontal class="h-4 w-4" />
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Filter dashboard</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        Pilih periode, role, dan karyawan untuk memperbarui insight KPI secara instan.
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                @click="$emit('apply')"
            >
                <Filter class="h-4 w-4" />
                Terapkan
            </button>
        </div>

        <div class="mt-4 grid gap-3 lg:grid-cols-4">
            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                    Periode
                </label>
                <Input v-model="monthValue" type="month" class="h-11 rounded-2xl border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-900" />
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                    Role
                </label>
                <Select
                    v-model="roleValue"
                    :options="roleOptions"
                    placeholder="Semua role"
                    class="h-11 rounded-2xl border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-900"
                />
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                    Karyawan
                </label>
                <Select
                    v-model="employeeValue"
                    :options="employeeOptions"
                    placeholder="Semua karyawan"
                    class="h-11 rounded-2xl border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-900"
                />
            </div>

            <div class="space-y-2">
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                    Search table
                </label>
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        :model-value="search"
                        placeholder="Cari nama atau role..."
                        class="h-11 rounded-2xl border-slate-200/80 bg-slate-50 pl-10 dark:border-slate-800 dark:bg-slate-900"
                        @update:model-value="$emit('update:search', $event)"
                    />
                </div>
            </div>
        </div>
    </Card>
</template>
