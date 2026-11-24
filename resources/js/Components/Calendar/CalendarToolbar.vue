<template>
    <section class="ui-card flex flex-col gap-3 p-3 lg:flex-row lg:items-center lg:justify-between" aria-label="Controles de la agenda">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="ui-btn-primary" @click="$emit('new')">
                <UiIcon name="plus" size="h-4 w-4" />
                Nueva cita
            </button>
            <button type="button" class="ui-btn-secondary px-3" @click="$emit('today')">
                <UiIcon name="today" size="h-4 w-4" />
                Hoy
            </button>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="ui-btn-secondary h-9 w-9 px-0"
                    aria-label="Intervalo anterior"
                    @click="$emit('prev')"
                >
                    <UiIcon name="chevron-left" size="h-4 w-4" />
                </button>
                <button
                    type="button"
                    class="ui-btn-secondary h-9 w-9 px-0"
                    aria-label="Intervalo siguiente"
                    @click="$emit('next')"
                >
                    <UiIcon name="chevron-right" size="h-4 w-4" />
                </button>
            </div>
            <label class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <span class="sr-only">Ir a fecha</span>
                <input
                    type="date"
                    class="ui-input w-auto py-1.5 text-xs"
                    :value="date"
                    @input="$emit('update:date', $event.target.value)"
                />
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800" role="group" aria-label="Vista del calendario">
            <button
                v-for="option in options"
                :key="option.value"
                type="button"
                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition"
                :class="view === option.value
                    ? 'bg-white text-brand-700 shadow-sm dark:bg-slate-900 dark:text-brand-300'
                    : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'"
                :aria-pressed="view === option.value"
                @click="$emit('update:view', option.value)"
            >
                <UiIcon :name="option.icon" size="h-4 w-4" />
                {{ option.label }}
            </button>
        </div>
    </section>
</template>

<script>
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'CalendarToolbar',
    components: { UiIcon },
    props: {
        view: { type: String, default: 'month' },
        date: { type: String, default: '' },
    },
    emits: ['new', 'today', 'prev', 'next', 'update:date', 'update:view'],
    data() {
        return {
            options: [
                { value: 'month', label: 'Mes', icon: 'calendar-month' },
                { value: 'week', label: 'Semana', icon: 'calendar-week' },
                { value: 'day', label: 'Día', icon: 'calendar-day' },
            ],
        };
    },
};
</script>
