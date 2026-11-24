<template>
    <Head :title="specialty.name" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Catálogo clínico"
                :title="specialty.name"
                :description="`${specialty.slug ?? ''} · ${specialty.active ? 'Activa' : 'Inactiva'}`"
            >
                <template #actions>
                    <Link :href="route('specialties.edit', specialty.id)" class="ui-btn-secondary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                    <Link :href="route('specialties.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="ui-card p-6">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Descripción</h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ specialty.description || 'Sin descripción registrada.' }}
                </p>
            </section>

            <section class="ui-card overflow-hidden lg:col-span-2">
                <h2 class="border-b border-slate-200 px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    Médicos de esta especialidad
                </h2>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="doctor in doctors" :key="doctor.id" class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                        <div class="min-w-0">
                            <Link :href="route('doctors.show', doctor.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                Dr. {{ fullName(doctor.user) }}
                            </Link>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ doctor.user?.email }}</p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="doctor.active
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20'
                                : 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20'"
                        >
                            {{ doctor.active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </li>
                    <li v-if="!doctors.length" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        No hay médicos asignados a esta especialidad.
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        UiIcon,
    },
    props: {
        specialty: { type: Object, required: true },
        doctors: { type: Array, default: () => [] },
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
    },
};
</script>
