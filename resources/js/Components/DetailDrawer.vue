<script setup>
defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Detalle' },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-40 flex justify-end bg-slate-900/50" @click="emit('close')">
                <aside
                    class="h-full w-full max-w-lg overflow-y-auto bg-white shadow-2xl dark:bg-gray-900"
                    @click.stop
                >
                    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">{{ title }}</h2>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                            aria-label="Cerrar panel"
                            @click="emit('close')"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>
                    <div class="p-5">
                        <slot />
                    </div>
                </aside>
            </div>
        </Transition>
    </Teleport>
</template>
