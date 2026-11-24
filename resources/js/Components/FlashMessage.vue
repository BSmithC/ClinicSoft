<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);
const message = ref('');
const tone = ref('success');

const flash = computed(() => page.props.flash ?? {});

watch(
    () => [flash.value.success, flash.value.error],
    ([success, error]) => {
        if (error) {
            message.value = error;
            tone.value = 'error';
            visible.value = true;
        } else if (success) {
            message.value = success;
            tone.value = 'success';
            visible.value = true;
        }
    },
    { immediate: true }
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="visible"
            class="fixed bottom-5 right-5 z-50 flex max-w-sm items-start gap-3 rounded-xl border px-4 py-3 shadow-xl"
            :class="tone === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-200'"
            role="status"
        >
            <span class="mt-0.5 text-sm font-medium">{{ message }}</span>
            <button type="button" class="ms-auto text-xs font-semibold uppercase" @click="visible = false">Cerrar</button>
        </div>
    </Transition>
</template>
