<template>
    <Head title="Perfil médico" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Dr. {{ doctor.user?.name }} {{ doctor.user?.last_name }}</h1>
                    <p class="text-sm text-slate-500">{{ doctor.user?.email }} · Licencia {{ doctor.license_number || '—' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('doctors.agenda', doctor.id)" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Ver agenda</Link>
                    <Link :href="route('doctors.edit', doctor.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Editar</Link>
                    <Link :href="route('doctors.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver</Link>
                </div>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Perfil</h2>
                <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">{{ doctor.bio || 'Sin biografía registrada.' }}</p>
                <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">Especialidades</p>
                <div class="flex flex-wrap gap-2">
                    <span v-for="specialty in doctor.specialties" :key="specialty.id" class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">{{ specialty.name }}</span>
                    <span v-if="!doctor.specialties?.length" class="text-sm text-slate-500">Sin especialidades asignadas.</span>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Próximas sesiones</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="session in sessions" :key="session.id" class="border-b border-slate-100 pb-2 last:border-0 dark:border-slate-800">
                        <Link :href="route('sessions.show', session.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ session.title }}</Link>
                        <p class="text-xs text-slate-500">{{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }} · {{ session.specialty?.name ?? 'Sin especialidad' }}</p>
                    </li>
                    <li v-if="!sessions.length" class="text-slate-500">No hay sesiones recientes.</li>
                </ul>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Últimas citas</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="appointment in appointments" :key="appointment.id" class="border-b border-slate-100 pb-2 last:border-0 dark:border-slate-800">
                        <Link :href="route('appointments.show', appointment.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">Cita #{{ appointment.id }}</Link>
                        <p class="text-xs text-slate-500">{{ formatDate(appointment.date) }} · {{ appointment.patient?.name }} {{ appointment.patient?.last_name }}</p>
                        <StatusBadge :status="appointment.status" />
                    </li>
                    <li v-if="!appointments.length" class="text-slate-500">No hay citas recientes.</li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
        appointments: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
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
