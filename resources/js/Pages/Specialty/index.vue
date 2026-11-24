<template>
    <Head title="Especialidades" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Especialidades</h1>
                    <p class="text-sm text-slate-500">Catálogo clínico usado al programar sesiones y citas.</p>
                </div>
                <Link :href="route('specialties.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nueva especialidad</Link>
            </div>
        </template>

        <form @submit.prevent="submit" class="mb-5 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row">
            <input v-model="form.search" type="search" placeholder="Buscar por nombre o descripción" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white dark:bg-slate-700">Buscar</button>
        </form>

        <section v-if="specialties.data.length" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Especialidad</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3">Médicos</th>
                            <th class="px-4 py-3">Sesiones</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="specialty in specialties.data" :key="specialty.id">
                            <td class="px-4 py-3">
                                <Link :href="route('specialties.show', specialty.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ specialty.name }}</Link>
                                <p class="text-xs text-slate-500">{{ specialty.description }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ specialty.slug }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ specialty.doctors_count }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ specialty.sessions_count }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="specialty.active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'">
                                    {{ specialty.active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-3 text-xs font-semibold">
                                    <Link :href="route('specialties.edit', specialty.id)" class="text-amber-600 hover:underline">Editar</Link>
                                    <button v-if="specialty.active" type="button" class="text-rose-600 hover:underline" @click="deactivate(specialty)">Desactivar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :pagination="specialties" :filters="activeFilters" />
        </section>

        <section v-else class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="font-medium text-slate-700 dark:text-slate-200">No hay especialidades que coincidan con la búsqueda.</p>
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
        specialties: {
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
            },
        };
    },
    computed: {
        activeFilters() {
            return this.form.search ? { search: this.form.search } : {};
        },
    },
    methods: {
        submit() {
            router.get(route('specialties.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        deactivate(specialty) {
            if (!window.confirm(`¿Desactivar ${specialty.name}? No se eliminarán sus sesiones históricas.`)) return;
            router.delete(route('specialties.destroy', specialty.id), { preserveScroll: true });
        },
    },
};
</script>
