<template>
    <Head title="Pacientes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Pacientes</h1>
                    <p class="text-sm text-slate-500">Pacientes registrados y atendidos por el personal autorizado.</p>
                </div>
                <Link :href="route('patients.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nuevo paciente</Link>
            </div>
        </template>

        <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input v-model="form.search" type="search" placeholder="Buscar por nombre, DNI, correo o fecha" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" @keyup.enter="submit" />
                <select v-model="form.lastDays" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" @change="submit">
                    <option value="7">Últimos 7 días</option>
                    <option value="30">Últimos 30 días</option>
                    <option value="month">Este mes</option>
                    <option value="year">Este año</option>
                    <option value="all">Todos</option>
                </select>
                <button type="button" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-700" @click="submit">Buscar</button>
            </div>
        </section>

        <section v-if="patients.data.length" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">DNI</th>
                            <th class="px-4 py-3">Nacimiento</th>
                            <th class="px-4 py-3">Seguro</th>
                            <th class="px-4 py-3">Citas</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="patient in patients.data" :key="patient.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <Link :href="route('patients.show', patient.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ patient.name }} {{ patient.last_name }}</Link>
                                <p class="text-xs text-slate-500">{{ patient.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ patient.DNI }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ formatDate(patient.date_of_birth) }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ patient.insurance || 'Sin seguro' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ patient.appointment_count }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="patient.status ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'">
                                    {{ patient.status ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-3 text-xs font-semibold">
                                    <Link :href="route('patients.history', patient.id)" class="text-violet-600 hover:underline">Historial</Link>
                                    <Link :href="route('patients.edit', patient.id)" class="text-amber-600 hover:underline">Editar</Link>
                                    <button v-if="patient.status" type="button" class="text-rose-600 hover:underline" @click="deactivate(patient)">Desactivar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :pagination="patients" :filters="activeFilters" />
        </section>

        <section v-else class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="font-medium text-slate-700 dark:text-slate-200">No hay pacientes para los filtros seleccionados.</p>
            <Link :href="route('patients.create')" class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:underline">Registrar paciente</Link>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        Pagination,
    },
    props: {
        patients: {
            type: Object,
            required: true,
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            form: {
                search: this.filters.search ?? '',
                lastDays: this.filters.lastDays ?? '30',
            },
        };
    },
    computed: {
        activeFilters() {
            return Object.fromEntries(
                Object.entries(this.form).filter(([, value]) => value !== '' && value !== null)
            );
        },
    },
    methods: {
        submit() {
            router.get(route('patients.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        deactivate(patient) {
            if (!window.confirm(`¿Desactivar a ${patient.name} ${patient.last_name}? Su historial se conservará.`)) return;

            router.delete(route('patients.destroy', patient.id), { preserveScroll: true });
        },
        formatDate(dateString) {
            if (!dateString) return '';
            return String(dateString).slice(0, 10);
        },
    },
};
</script>
