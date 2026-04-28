<script setup>
import { watch } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: String,
    description: String,
    class: { type: String, default: '' },
});

const emit = defineEmits(['update:open']);

watch(
    () => props.open,
    (val) => { document.body.style.overflow = val ? 'hidden' : ''; },
);

function close() { emit('update:open', false); }
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end justify-center px-0 pb-0 sm:items-center sm:px-4 sm:pb-4"
            >
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close" />

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
                    enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-to-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
                    appear
                >
                    <div
                        :class="cn(
                            'relative z-10 w-full rounded-t-2xl sm:rounded-xl',
                            'border border-slate-200 bg-white shadow-xl',
                            'dark:border-slate-700 dark:bg-slate-900',
                            'max-h-[90vh] flex flex-col',
                            'sm:max-w-md',
                            $props.class
                        )"
                        role="dialog"
                        aria-modal="true"
                    >
                        <!-- Header -->
                        <div v-if="title || description" class="flex-shrink-0 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 v-if="title" class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                        {{ title }}
                                    </h2>
                                    <p v-if="description" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ description }}
                                    </p>
                                </div>
                                <button
                                    class="flex-shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                                    @click="close"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6 6 18M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable body -->
                        <div class="flex-1 overflow-y-auto px-5 py-4">
                            <slot />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
