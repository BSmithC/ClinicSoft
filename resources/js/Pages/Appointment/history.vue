<template>
    <Head title="Historial de cita" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Historial de la cita #{{ appointment.id }}</h1>
                    <p class="text-sm text-slate-500">{{ appointment.patient?.name }} {{ appointment.patient?.last_name }} · Dr. {{ appointment.doctor?.name }} {{ appointment.doctor?.last_name }}</p>
                </div>
                <Link :href="route('appointments.show', appointment.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver al detalle</Link>
            </div>
        </template>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Fecha del cambio</th>
                            <th class="px-4 py-3">Estado anterior</th>
                            <th class="px-4 py-3">Estado nuevo</th>
                            <th class="px-4 py-3">Reprogramación</th>
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="history in histories" :key="history.id">
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ history.created_at }}</td>
                            <td class="px-4 py-3"><StatusBadge v-if="history.from_status" :status="history.from_status" /><span v-else class="text-slate-400">—</span></td>
                            <td class="px-4 py-3"><StatusBadge :status="history.to_status" /></td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                <template v-if="history.from_date || history.to_date">
                                    {{ formatDate(history.from_date) }} {{ formatTime(history.from_start) }}
                                    → {{ formatDate(history.to_date) }} {{ formatTime(history.to_start) }}
                                </template>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ history.actor?.name ?? 'Sistema' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ history.reason ?? '—' }}</td>
                        </tr>
                        <tr v-if="!histories.length">
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No hay movimientos registrados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
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
        appointment: {
            type: Object,
            required: true,
        },
        histories: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return '';
            return String(dateString).slice(0, 10);
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '';
        },
    },
};
</script>
