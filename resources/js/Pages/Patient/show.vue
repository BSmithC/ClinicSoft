<template>
    <Head :title="`${patients.name ?? ''} ${patients.last_name ?? ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`${patients.name ?? ''} ${patients.last_name ?? ''}`"
                :description="`DNI ${patients.DNI ?? '—'} · ${patients.email ?? 'Sin correo'}`"
            >
                <template #actions>
                    <Link :href="route('patients.history', patients.id)" class="ui-btn-secondary">
                        <UiIcon name="clipboard" size="h-4 w-4" />
                        Historial clínico
                    </Link>
                    <Link :href="route('patients.edit', patients.id)" class="ui-btn-secondary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                    <Link :href="route('patients.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="ui-card p-6 lg:col-span-2">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Datos registrados</h2>
                <dl class="grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                    <div v-for="field in patientFields" :key="field.label" class="border-b border-slate-100 pb-4 last:border-0 dark:border-slate-800">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ field.label }}</dt>
                        <dd class="mt-1 font-medium text-slate-800 dark:text-white">{{ field.value }}</dd>
                    </div>
                </dl>
            </section>

            <section class="ui-card p-5">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Citas registradas</h2>
                <ul class="space-y-3">
                    <li v-for="appointment in history" :key="appointment.id" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="flex items-center justify-between gap-2">
                            <Link :href="route('appointments.show', appointment.id)" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                Cita #{{ appointment.id }}
                            </Link>
                            <StatusBadge :status="appointment.status" />
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ formatDate(appointment.date) }} · Dr. {{ fullName(appointment.doctor) }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ appointment.session?.title ?? appointment.title ?? '—' }}
                        </p>
                    </li>
                    <li v-if="!history.length" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        Este paciente aún no tiene citas registradas.
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
import StatusBadge from '@/Components/StatusBadge.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        StatusBadge,
        UiIcon,
    },
    props: {
        patients: { type: Object, required: true },
        history: { type: Array, default: () => [] },
    },
    computed: {
        patientFields() {
            const p = this.patients;
            return [
                { label: 'Nombre completo', value: [p.name, p.sur_name, p.last_name, p.second_sur_name].filter(Boolean).join(' ') || '—' },
                { label: 'Nacimiento', value: `${this.formatDate(p.date_of_birth)} · ${p.place_of_birth || '—'}` },
                { label: 'Teléfono', value: p.phone_number || '—' },
                { label: 'Dirección', value: p.address || '—' },
                { label: 'Género / sexo', value: `${p.gender ?? '—'} / ${p.sex ?? '—'}` },
                { label: 'Estado civil', value: p.marital_status || '—' },
                { label: 'Nacionalidad', value: p.nacionality || '—' },
                { label: 'Ocupación', value: p.ocupation || '—' },
                { label: 'Tipo de sangre', value: p.blood_type || '—' },
                { label: 'Seguro', value: p.insurance || '—' },
                { label: 'Estado', value: p.status ? 'Activo' : 'Inactivo' },
            ];
        },
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return String(dateString).slice(0, 10);
        },
    },
};
</script>
