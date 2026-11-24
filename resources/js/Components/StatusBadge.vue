<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: [String, Object], default: 'pending' },
});

const value = computed(() =>
    typeof props.status === 'string' ? props.status : (props.status?.value ?? 'pending')
);

const styles = {
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    confirmed: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
    waiting: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200',
    in_consultation: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    cancelled: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    no_show: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    scheduled: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
    active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    paused: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
};

const labels = {
    pending: 'Pendiente',
    confirmed: 'Confirmada',
    waiting: 'En espera',
    in_consultation: 'En consulta',
    completed: 'Completada',
    cancelled: 'Cancelada',
    no_show: 'No asistió',
    scheduled: 'Programada',
    active: 'Activa',
    paused: 'Pausada',
};

const classes = computed(() => styles[value.value] ?? styles.pending);
const label = computed(() => labels[value.value] ?? value.value.replaceAll('_', ' '));
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="classes">
        {{ label }}
    </span>
</template>
