<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ToastNotification from '@/Components/ToastNotification.vue';

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
    <ToastNotification
        :visible="visible"
        :message="message"
        :tone="tone"
        @close="visible = false"
    />
</template>
