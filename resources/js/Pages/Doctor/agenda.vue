<template>
    <Head title="Agenda del médico" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                :title="`Agenda de Dr. ${doctorName}`"
                description="Sesiones y citas en el rango seleccionado."
            >
                <template #actions>
                    <Link :href="route('agenda.index')" class="ui-btn-secondary">
                        <UiIcon name="calendar" size="h-4 w-4" />
                        Agenda general
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <form @submit.prevent="submit" class="ui-card flex flex-col gap-4 p-4 sm:flex-row sm:items-end">
                <FormField label="Desde" name="from">
                    <input id="from" v-model="form.from" type="date" class="ui-input" />
                </FormField>
                <FormField label="Hasta" name="to">
                    <input id="to" v-model="form.to" type="date" class="ui-input" />
                </FormField>
                <button type="submit" class="ui-btn-primary w-full sm:w-auto">
                    <UiIcon name="filter" size="h-4 w-4" />
                    Filtrar
                </button>
            </form>

            <div v-if="sessions.length" class="grid gap-5 xl:grid-cols-2">
                <section v-for="session in sessions" :key="session.id" class="ui-card p-5">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <Link :href="route('sessions.show', session.id)" class="font-semibold text-slate-800 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                {{ session.title }}
                            </Link>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }} · {{ session.specialty?.name ?? 'Sin especialidad' }}
                            </p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ session.appointments?.length ?? 0 }} citas
                        </span>
                    </div>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="appointment in session.appointments" :key="appointment.id" class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                            <span class="text-slate-700 dark:text-slate-200">
                                {{ formatTime(appointment.starttime) }} · {{ fullName(appointment.patient) }}
                            </span>
                            <div class="flex items-center gap-3">
                                <StatusBadge :status="appointment.status" />
                                <Link :href="route('appointments.show', appointment.id)" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                    Ver
                                </Link>
                            </div>
                        </li>
                        <li v-if="!session.appointments?.length" class="py-3 text-sm text-slate-500 dark:text-slate-400">
                            Sin citas asignadas a esta sesión.
                        </li>
                    </ul>
                </section>
            </div>

            <div v-else class="ui-card">
                <EmptyState
                    title="No hay sesiones programadas"
                    description="No hay sesiones que coincidan con el rango seleccionado."
                    icon="calendar"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormField from '@/Components/FormField.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        StatusBadge,
        EmptyState,
        UiIcon,
    },
    props: {
        doctor: { type: Object, required: true },
        sessions: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            form: {
                from: this.filters.from ?? '',
                to: this.filters.to ?? '',
            },
        };
    },
    computed: {
        doctorName() {
            const user = this.doctor.user;
            if (!user) return `#${this.doctor.id}`;
            return `${user.name ?? ''} ${user.last_name ?? ''}`.trim();
        },
    },
    methods: {
        submit() {
            router.get(route('doctors.agenda', this.doctor.id), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        fullName(person) {
            if (!person) return 'Paciente no disponible';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || 'Paciente no disponible';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return String(dateString).slice(0, 10);
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '—';
        },
    },
};
</script>
