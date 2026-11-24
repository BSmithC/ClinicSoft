<template>
    <Head :title="`Historia médica #${medicalhistories.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Historia médica #${medicalhistories.id}`"
                :description="`${fullName(medicalhistories.patient)} · Dr. ${fullName(medicalhistories.doctor)}`"
            >
                <template #actions>
                    <Link :href="route('medicalhistories.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver
                    </Link>
                    <Link :href="route('medicalhistories.edit', medicalhistories.id)" class="ui-btn-primary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="ui-card p-6 lg:col-span-2">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Diagnóstico y evolución</h2>
                <dl class="grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                    <div v-for="field in clinicalFields" :key="field.label" class="border-b border-slate-100 pb-4 last:border-0 dark:border-slate-800">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ field.label }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-slate-800 dark:text-slate-100">{{ field.value }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="space-y-6">
                <section class="ui-card p-5">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Constantes registradas</h2>
                    <dl class="space-y-3 text-sm">
                        <div v-for="vital in vitals" :key="vital.label" class="flex justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">{{ vital.label }}</dt>
                            <dd class="font-semibold text-slate-800 dark:text-white">{{ vital.value }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="ui-card p-5">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Relaciones clínicas</h2>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <p class="text-slate-500 dark:text-slate-400">Paciente</p>
                            <Link v-if="medicalhistories.patient" :href="route('patients.show', medicalhistories.patient.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                {{ fullName(medicalhistories.patient) }}
                            </Link>
                            <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                        </li>
                        <li>
                            <p class="text-slate-500 dark:text-slate-400">Médico</p>
                            <span class="font-semibold text-slate-800 dark:text-white">Dr. {{ fullName(medicalhistories.doctor) }}</span>
                        </li>
                        <li>
                            <p class="text-slate-500 dark:text-slate-400">Medicamento</p>
                            <Link v-if="medicalhistories.drug" :href="route('drugs.show', medicalhistories.drug.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                {{ medicalhistories.drug.name }}
                            </Link>
                            <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                        </li>
                    </ul>
                </section>
            </aside>
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
        medicalhistories: { type: Object, required: true },
    },
    computed: {
        clinicalFields() {
            return [
                { label: 'Diagnóstico', value: this.medicalhistories.diagnosis || '—' },
                { label: 'Descripción del diagnóstico', value: this.medicalhistories.diagnosis_description || '—' },
                { label: 'Motivo', value: this.medicalhistories.reason || '—' },
                { label: 'Síntomas', value: this.medicalhistories.symptoms || '—' },
                { label: 'Descripción', value: this.medicalhistories.description || '—' },
                { label: 'Observaciones', value: this.medicalhistories.observation || '—' },
            ];
        },
        vitals() {
            return [
                { label: 'Presión arterial', value: this.medicalhistories.blood_pressure || '—' },
                { label: 'Temperatura', value: this.medicalhistories.temperature || '—' },
                { label: 'Peso', value: this.medicalhistories.weight || '—' },
                { label: 'Frecuencia cardíaca', value: this.medicalhistories.heart_rate || '—' },
            ];
        },
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
    },
};
</script>
