<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: String, required: true },
    tabs: {
        type: Array,
        default: () => [],
    },
    class: { type: String, default: '' },
    listClass: { type: String, default: '' },
    triggerClass: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const currentTab = computed(() => props.modelValue);

function selectTab(value) {
    emit('update:modelValue', value);
}
</script>

<template>
    <div :class="cn('space-y-4', $props.class)">
        <div
            :class="cn(
                'inline-flex rounded-2xl border border-slate-200 bg-white/80 p-1 shadow-sm dark:border-slate-800 dark:bg-slate-900/80',
                listClass
            )"
        >
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                :class="cn(
                    'inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium text-slate-500 transition',
                    'hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100',
                    currentTab === tab.value && 'bg-slate-900 text-white shadow-sm dark:bg-slate-100 dark:text-slate-900',
                    triggerClass
                )"
                @click="selectTab(tab.value)"
            >
                <slot name="icon" :tab="tab" />
                <span>{{ tab.label }}</span>
            </button>
        </div>

        <slot :value="currentTab" />
    </div>
</template>
