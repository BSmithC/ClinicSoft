<template>
    <div class="animate-pulse" role="status" :aria-label="label">
        <div class="flex items-center gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/50">
            <div v-for="column in columns" :key="column" class="h-3 flex-1 rounded bg-slate-200 dark:bg-slate-700" />
        </div>
        <div v-for="row in rows" :key="row" class="flex items-center gap-4 border-b border-slate-100 px-4 py-4 dark:border-slate-800">
            <div v-for="column in columns" :key="column" class="h-4 flex-1 rounded bg-slate-100 dark:bg-slate-800" />
        </div>
        <span class="sr-only">{{ label }}</span>
    </div>
</template>

<script>
export default {
    name: 'LoadingState',
    props: {
        rows: { type: Number, default: 4 },
        columns: { type: Number, default: 4 },
        label: { type: String, default: 'Cargando datos...' },
    },
};
</script>
