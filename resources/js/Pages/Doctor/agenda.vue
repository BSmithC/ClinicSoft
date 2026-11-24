<template>
    <Head title="Agenda del médico" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Agenda de Dr. {{ doctor.user?.name }} {{ doctor.user?.last_name }}</h1>
                    <p class="text-sm text-slate-500">Sesiones y citas en el rango seleccionado.</p>
                </div>
                <Link :href="route('agenda.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Agenda general</Link>
            </div>
        </template>

        <form @submit.prevent="submit" class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <label for="from" class="mb-1 block text-xs font-medium text-slate-500">Desde</label>
                <input id="from" v-model="form.from" type="date" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs font-medium text-slate-500">Hasta</label>
                <input id="to" v-model="form.to" type="date" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
            </div>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white dark:bg-slate-700">Filtrar</button>
        </form>

        <div class="space-y-4">
            <section v-for="session in sessions" :key="session.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <Link :href="route('sessions.show', session.id)" class="text-base font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ session.title }}</Link>
                        <p class="text-xs text-slate-500">{{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }} · {{ session.specialty?.name ?? 'Sin especialidad' }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ session.appointments?.length ?? 0 }} citas</span>
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="appointment in session.appointments" :key="appointment.id" class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">{{ formatTime(appointment.starttime) }} · {{ appointment.patient?.name }} {{ appointment.patient?.last_name }}</span>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="appointment.status" />
                            <Link :href="route('appointments.show', appointment.id)" class="text-xs font-semibold text-blue-600 hover:underline">Ver</Link>
                        </div>
                    </li>
                    <li v-if="!session.appointments?.length" class="py-2 text-sm text-slate-500">Sin citas asignadas a esta sesión.</li>
                </ul>
            </section>
            <section v-if="!sessions.length" class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="font-medium text-slate-700 dark:text-slate-200">No hay sesiones programadas en ese rango.</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        StatusBadge,
    },
    props: {
        doctor: {
            type: Object,
            required: true,
        },
        sessions: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            form: {
                from: this.filters.from ?? '',
                to: this.filters.to ?? '',
            },
        };
    },
    methods: {
        submit() {
            router.get(route('doctors.agenda', this.doctor.id), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
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
