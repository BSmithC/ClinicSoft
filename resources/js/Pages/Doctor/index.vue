<template>
    <Head title="Médicos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Médicos</h1>
                    <p class="text-sm text-slate-500">Perfiles clínicos registrados en el sistema.</p>
                </div>
                <Link v-if="canCreate" :href="route('doctors.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nuevo médico</Link>
            </div>
        </template>

        <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input v-model="form.search" type="search" placeholder="Buscar por nombre o correo" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" @keyup.enter="submit" />
                <select v-model="form.active" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" @change="submit">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
                <button type="button" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white dark:bg-slate-700" @click="submit">Buscar</button>
            </div>
        </section>

        <section v-if="doctors.data.length" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3">Licencia</th>
                            <th class="px-4 py-3">Especialidades</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="doctor in doctors.data" :key="doctor.id">
                            <td class="px-4 py-3">
                                <Link :href="route('doctors.show', doctor.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">Dr. {{ doctor.user?.name }} {{ doctor.user?.last_name }}</Link>
                                <p class="text-xs text-slate-500">{{ doctor.user?.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ doctor.license_number || '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ (doctor.specialties ?? []).map(item => item.name).join(', ') || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="doctor.active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'">
                                    {{ doctor.active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-3 text-xs font-semibold">
                                    <Link :href="route('doctors.agenda', doctor.id)" class="text-sky-600 hover:underline">Agenda</Link>
                                    <Link :href="route('doctors.edit', doctor.id)" class="text-amber-600 hover:underline">Editar</Link>
                                    <button v-if="doctor.active" type="button" class="text-rose-600 hover:underline" @click="deactivate(doctor)">Desactivar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :pagination="doctors" :filters="activeFilters" />
        </section>

        <section v-else class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="font-medium text-slate-700 dark:text-slate-200">No hay médicos para los filtros seleccionados.</p>
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
        doctors: {
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
                active: this.filters.active ?? '',
            },
        };
    },
    computed: {
        activeFilters() {
            return Object.fromEntries(Object.entries(this.form).filter(([, value]) => value !== ''));
        },
        canCreate() {
            return (this.$page.props.auth?.user?.roles ?? []).includes('admin');
        },
    },
    methods: {
        submit() {
            router.get(route('doctors.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        deactivate(doctor) {
            if (!window.confirm(`¿Desactivar el perfil de ${doctor.user?.name}? Su historial se conservará.`)) return;
            router.delete(route('doctors.destroy', doctor.id), { preserveScroll: true });
        },
    },
};
</script>
