<template>
    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
            <UiIcon :name="icon" size="h-6 w-6" />
        </span>
        <h3 class="mt-4 text-sm font-semibold text-slate-800 dark:text-white">
            {{ title }}
        </h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">
            {{ description }}
        </p>
        <div v-if="$slots.actions" class="mt-5 flex flex-wrap justify-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script>
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'EmptyState',
    components: { UiIcon },
    props: {
        title: { type: String, default: 'No hay resultados' },
        description: { type: String, default: '' },
        icon: { type: String, default: 'information' },
    },
};
</script>
