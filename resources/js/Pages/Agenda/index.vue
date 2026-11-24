<template>
    <Head title="Agenda general" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Agenda general</h1>
                <p class="text-sm text-slate-500">{{ isAdmin ? 'Intervalo y médico seleccionables.' : 'Se muestran únicamente sus sesiones y citas.' }}</p>
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
            <div v-if="isAdmin">
                <label for="doctor_id" class="mb-1 block text-xs font-medium text-slate-500">Médico</label>
                <select id="doctor_id" v-model="form.doctor_id" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Todos</option>
                    <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.user_id">{{ doctor.user?.name }} {{ doctor.user?.last_name }}</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white dark:bg-slate-700">Filtrar</button>
        </form>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="border-b border-slate-100 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-800 dark:text-white">Sesiones programadas</h2>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="session in sessions" :key="session.id" class="px-5 py-3 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <Link :href="route('sessions.show', session.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ session.title }}</Link>
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ session.status }}</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }} · Dr. {{ session.doctor?.name }} {{ session.doctor?.last_name }}</p>
                        <p class="text-xs text-slate-500">{{ session.specialty?.name ?? 'Sin especialidad' }} · {{ session.appointments?.length ?? 0 }} citas</p>
                    </li>
                    <li v-if="!sessions.length" class="px-5 py-8 text-center text-sm text-slate-500">No hay sesiones en el rango seleccionado.</li>
                </ul>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="border-b border-slate-100 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-800 dark:text-white">Citas del intervalo</h2>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="appointment in appointments" :key="appointment.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div>
                            <Link :href="route('appointments.show', appointment.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">Cita #{{ appointment.id }}</Link>
                            <p class="text-xs text-slate-500">{{ formatDate(appointment.date) }} · {{ formatTime(appointment.starttime) }} · {{ appointment.patient?.name }} {{ appointment.patient?.last_name }}</p>
                            <p class="text-xs text-slate-500">Dr. {{ appointment.doctor?.name }} {{ appointment.doctor?.last_name }} · {{ appointment.session?.title ?? 'Independiente' }}</p>
                        </div>
                        <StatusBadge :status="appointment.status" />
                    </li>
                    <li v-if="!appointments.length" class="px-5 py-8 text-center text-sm text-slate-500">No hay citas en el rango seleccionado.</li>
                </ul>
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
        sessions: {
            type: Array,
            default: () => [],
        },
        appointments: {
            type: Array,
            default: () => [],
        },
        doctors: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        isAdmin: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            form: {
                from: this.filters.from ?? '',
                to: this.filters.to ?? '',
                doctor_id: this.filters.doctor_id ?? '',
            },
        };
    },
    methods: {
        submit() {
            router.get(route('agenda.index'), this.form, {
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
