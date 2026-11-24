<template>
    <Head title="Ficha del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ patients.name }} {{ patients.last_name }}</h1>
                    <p class="text-sm text-slate-500">DNI {{ patients.DNI }} · {{ patients.email }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('patients.history', patients.id)" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Historial clínico</Link>
                    <Link :href="route('patients.edit', patients.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Editar</Link>
                    <Link :href="route('patients.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver</Link>
                </div>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Datos registrados</h2>
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Nombre completo</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.name }} {{ patients.sur_name }} {{ patients.last_name }} {{ patients.second_sur_name }}</dd></div>
                    <div><dt class="text-slate-500">Nacimiento</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ formatDate(patients.date_of_birth) }} · {{ patients.place_of_birth }}</dd></div>
                    <div><dt class="text-slate-500">Teléfono</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.phone_number || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Dirección</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.address || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Género / sexo</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.gender }} / {{ patients.sex }}</dd></div>
                    <div><dt class="text-slate-500">Estado civil</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.marital_status }}</dd></div>
                    <div><dt class="text-slate-500">Nacionalidad</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.nacionality || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Ocupación</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.ocupation || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Tipo de sangre</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.blood_type || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Seguro</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.insurance || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Estado</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ patients.status ? 'Activo' : 'Inactivo' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Citas registradas</h2>
                <ul class="space-y-3">
                    <li v-for="appointment in history" :key="appointment.id" class="rounded-lg border border-slate-100 p-3 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-2">
                            <Link :href="route('appointments.show', appointment.id)" class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400">Cita #{{ appointment.id }}</Link>
                            <StatusBadge :status="appointment.status" />
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ formatDate(appointment.date) }} · Dr. {{ appointment.doctor?.name }} {{ appointment.doctor?.last_name }}</p>
                        <p class="text-xs text-slate-500">{{ appointment.session?.title ?? appointment.title }}</p>
                    </li>
                    <li v-if="!history.length" class="py-6 text-center text-sm text-slate-500">Este paciente aún no tiene citas registradas.</li>
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
        patients: {
            type: Object,
            required: true,
        },
        history: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return '—';
            return String(dateString).slice(0, 10);
        },
    },
};
</script>
