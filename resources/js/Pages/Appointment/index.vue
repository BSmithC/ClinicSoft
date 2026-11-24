<template>
    <Head title="Gestión de citas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Gestión de citas</h1>
                    <p class="text-sm text-slate-500">Citas registradas en el sistema, ordenadas por fecha.</p>
                </div>
                <Link :href="route('appointments.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nueva cita</Link>
            </div>
        </template>

        <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Buscar por paciente, motivo o fecha"
                    class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    @keyup.enter="applyFilters"
                />
                <select v-model="filters.lastDays" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" @change="applyFilters">
                    <option value="7">Últimos 7 días</option>
                    <option value="30">Últimos 30 días</option>
                    <option value="month">Este mes</option>
                    <option value="year">Este año</option>
                    <option value="all">Todos</option>
                </select>
                <button type="button" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-700" @click="applyFilters">Buscar</button>
            </div>
        </section>

        <section v-if="appointments.data.length" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Cita</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3">Fecha y hora</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="appointment in appointments.data" :key="appointment.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <Link :href="route('appointments.show', appointment.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">#{{ appointment.id }}</Link>
                                <p class="text-xs text-slate-500">{{ appointment.session?.title ?? appointment.title }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ appointment.patient?.name }} {{ appointment.patient?.last_name }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">Dr. {{ appointment.doctor?.name }} {{ appointment.doctor?.last_name }}</td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ formatDate(appointment.date) }} · {{ formatTime(appointment.starttime) }}–{{ formatTime(appointment.endtime) }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="appointment.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <select
                                        v-model="statusDrafts[appointment.id]"
                                        class="rounded-lg border-slate-300 text-xs dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                                        :disabled="isTerminal(appointment)"
                                    >
                                        <option value="">Cambiar estado…</option>
                                        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                                    </select>
                                    <button
                                        v-if="statusDrafts[appointment.id]"
                                        type="button"
                                        class="rounded-lg bg-sky-600 px-2 py-1 text-xs font-semibold text-white hover:bg-sky-700"
                                        @click="changeStatus(appointment)"
                                    >Aplicar</button>
                                    <Link :href="route('appointments.history', appointment.id)" class="text-xs font-semibold text-violet-600 hover:underline">Historial</Link>
                                    <Link :href="route('appointments.edit', appointment.id)" class="text-xs font-semibold text-amber-600 hover:underline">Editar</Link>
                                    <button
                                        v-if="statusValue(appointment) !== 'cancelled'"
                                        type="button"
                                        class="text-xs font-semibold text-rose-600 hover:underline"
                                        @click="cancelAppointment(appointment)"
                                    >Cancelar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :pagination="appointments" :filters="activeFilters" />
        </section>

        <section v-else class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="font-medium text-slate-700 dark:text-slate-200">No hay citas para los filtros seleccionados.</p>
            <Link :href="route('appointments.create')" class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:underline">Registrar la primera cita</Link>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        Pagination,
        StatusBadge,
    },
    props: {
        appointments: {
            type: Object,
            required: true,
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        statuses: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            filtersForm: {
                search: this.filters.search ?? '',
                lastDays: this.filters.lastDays ?? '30',
            },
            statusDrafts: {},
        };
    },
    computed: {
        activeFilters() {
            return Object.fromEntries(
                Object.entries(this.filtersForm).filter(([, value]) => value !== '' && value !== null)
            );
        },
    },
    methods: {
        applyFilters() {
            router.get(route('appointments.index'), this.filtersForm, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        statusValue(appointment) {
            const status = appointment.status;
            return status && typeof status === 'object' ? status.value : status || 'pending';
        },
        isTerminal(appointment) {
            return ['completed', 'cancelled', 'no_show'].includes(this.statusValue(appointment));
        },
        changeStatus(appointment) {
            const status = this.statusDrafts[appointment.id];
            if (!status) return;

            router.post(route('appointments.status', appointment.id), {
                status,
                reason: 'Actualización desde el listado de citas.',
            }, { preserveScroll: true, onSuccess: () => { this.statusDrafts[appointment.id] = ''; } });
        },
        cancelAppointment(appointment) {
            if (!window.confirm(`¿Cancelar la cita #${appointment.id}? El espacio quedará disponible.`)) return;
            router.delete(route('appointments.destroy', appointment.id), {
                data: { reason: 'Cancelada desde el listado de citas.' },
                preserveScroll: true,
            });
        },
        formatDate(dateString) {
            if (!dateString) return '';
            return new Intl.DateTimeFormat('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(new Date(`${String(dateString).slice(0, 10)}T12:00:00`));
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '';
        },
    },
};
</script>
