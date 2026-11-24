<template>
    <Head title="Detalle de especialidad" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ specialty.name }}</h1>
                    <p class="text-sm text-slate-500">{{ specialty.slug }} · {{ specialty.active ? 'Activa' : 'Inactiva' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('specialties.edit', specialty.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Editar</Link>
                    <Link :href="route('specialties.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver</Link>
                </div>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-1">
                <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Descripción</h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ specialty.description || 'Sin descripción registrada.' }}</p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Médicos de esta especialidad</h2>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="doctor in doctors" :key="doctor.id" class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div>
                            <Link :href="route('doctors.show', doctor.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">Dr. {{ doctor.user?.name }} {{ doctor.user?.last_name }}</Link>
                            <p class="text-xs text-slate-500">{{ doctor.user?.email }}</p>
                        </div>
                        <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="doctor.active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'">
                            {{ doctor.active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </li>
                    <li v-if="!doctors.length" class="py-6 text-center text-sm text-slate-500">No hay médicos asignados a esta especialidad.</li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
    },
    props: {
        specialty: {
            type: Object,
            required: true,
        },
        doctors: {
            type: Array,
            default: () => [],
        },
    },
};
</script>
