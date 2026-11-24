<template>
    <AppLayout :show-header="Boolean($slots.header)">
        <template #header>
            <slot name="header" />
        </template>
        <slot />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>
