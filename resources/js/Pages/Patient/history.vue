<template>
    <Head title="Historial del paciente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Historial de {{ patient.name }} {{ patient.last_name }}</h1>
                    <p class="text-sm text-slate-500">DNI {{ patient.DNI }} · Solo personal autorizado.</p>
                </div>
                <Link :href="route('patients.show', patient.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver a la ficha</Link>
            </div>
        </template>

        <div class="space-y-6">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="border-b border-slate-100 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-800 dark:text-white">Evolución de citas</h2>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Cita</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Médico</th>
                                <th class="px-4 py-3">Sesión</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Movimientos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="appointment in appointments" :key="appointment.id">
                                <td class="px-4 py-3">
                                    <Link :href="route('appointments.show', appointment.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">#{{ appointment.id }}</Link>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ formatDate(appointment.date) }} · {{ formatTime(appointment.starttime) }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">Dr. {{ appointment.doctor?.name }} {{ appointment.doctor?.last_name }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ appointment.session?.title ?? 'Independiente' }}</td>
                                <td class="px-4 py-3"><StatusBadge :status="appointment.status" /></td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    <span v-for="history in appointment.histories" :key="history.id" class="mr-2 inline-block">
                                        {{ history.created_at }}: {{ history.to_status }}
                                    </span>
                                    <span v-if="!appointment.histories?.length">—</span>
                                </td>
                            </tr>
                            <tr v-if="!appointments.length">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">No hay citas registradas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="border-b border-slate-100 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-800 dark:text-white">Notas clínicas</h2>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Diagnóstico</th>
                                <th class="px-4 py-3">Motivo</th>
                                <th class="px-4 py-3">Síntomas</th>
                                <th class="px-4 py-3">Observaciones</th>
                                <th class="px-4 py-3">Constantes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="medicalHistory in medicalHistories" :key="medicalHistory.id">
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ formatDate(medicalHistory.created_at) }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ medicalHistory.diagnosis || medicalHistory.diagnosis_description || '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ medicalHistory.reason || '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ medicalHistory.symptoms || '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ medicalHistory.observation || medicalHistory.description || '—' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ medicalHistory.blood_pressure || '—' }} · {{ medicalHistory.temperature || '—' }} °C ·
                                    {{ medicalHistory.weight || '—' }} kg · {{ medicalHistory.heart_rate || '—' }} lpm
                                </td>
                            </tr>
                            <tr v-if="!medicalHistories.length">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">No hay notas clínicas registradas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
        patient: {
            type: Object,
            required: true,
        },
        appointments: {
            type: Array,
            default: () => [],
        },
        medicalHistories: {
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
