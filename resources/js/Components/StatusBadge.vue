<template>
    <span
        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
        :class="classes"
    >
        <span v-if="dot" class="h-1.5 w-1.5 shrink-0 rounded-full bg-current" aria-hidden="true" />
        {{ label }}
    </span>
</template>

<script>
const STATUS_STYLES = {
    // Citas
    pending: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
    confirmed: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20',
    waiting: 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-400/10 dark:text-violet-300 dark:ring-violet-400/20',
    in_consultation: 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20',
    cancelled: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20',
    no_show: 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600/40',
    // Sesiones médicas
    scheduled: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20',
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20',
    paused: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
    // Bloqueos de agenda
    blocked: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20',
};

const STATUS_LABELS = {
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
    blocked: 'Bloqueada',
};

const FALLBACK_STYLE = 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600/40';

export default {
    name: 'StatusBadge',
    props: {
        status: { type: [String, Object], default: 'pending' },
        dot: { type: Boolean, default: true },
    },
    computed: {
        value() {
            if (typeof this.status === 'string') return this.status;
            return this.status?.value ?? '';
        },
        classes() {
            return STATUS_STYLES[this.value] ?? FALLBACK_STYLE;
        },
        label() {
            if (STATUS_LABELS[this.value]) return STATUS_LABELS[this.value];
            if (!this.value) return 'Sin estado';
            const text = String(this.value).replaceAll('_', ' ');
            return text.charAt(0).toUpperCase() + text.slice(1);
        },
    },
};
</script>
