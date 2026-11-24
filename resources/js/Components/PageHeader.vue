<template>
    <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p v-if="eyebrow" class="mb-1 text-xs font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">
                {{ eyebrow }}
            </p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ title }}
            </h1>
            <p v-if="description" class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>

<script>
export default {
    name: 'PageHeader',
    props: {
        title: { type: String, required: true },
        description: { type: String, default: '' },
        eyebrow: { type: String, default: '' },
    },
};
</script>
