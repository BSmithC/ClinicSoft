<template>
    <Head title="Detalle de historia médica" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Historia médica #{{ medicalhistories.id }}</h1>
                    <p class="text-sm text-slate-500">
                        {{ medicalhistories.patient?.name }} {{ medicalhistories.patient?.last_name }} ·
                        Dr. {{ medicalhistories.doctor?.name }} {{ medicalhistories.doctor?.last_name }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('medicalhistories.edit', medicalhistories.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Editar</Link>
                    <Link :href="route('medicalhistories.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver</Link>
                </div>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Diagnóstico y evolución</h2>
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Diagnóstico</dt>
                        <dd class="font-semibold text-slate-800 dark:text-white">{{ medicalhistories.diagnosis }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Descripción del diagnóstico</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ medicalhistories.diagnosis_description || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Motivo</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ medicalhistories.reason || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Síntomas</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ medicalhistories.symptoms || '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Descripción</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ medicalhistories.description || '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Observaciones</dt>
                        <dd class="text-slate-700 dark:text-slate-200">{{ medicalhistories.observation || '—' }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Constantes registradas</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Presión arterial</dt>
                            <dd class="font-semibold text-slate-800 dark:text-white">{{ medicalhistories.blood_pressure || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Temperatura</dt>
                            <dd class="font-semibold text-slate-800 dark:text-white">{{ medicalhistories.temperature || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Peso</dt>
                            <dd class="font-semibold text-slate-800 dark:text-white">{{ medicalhistories.weight || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Frecuencia cardíaca</dt>
                            <dd class="font-semibold text-slate-800 dark:text-white">{{ medicalhistories.heart_rate || '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Relaciones clínicas</h2>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <p class="text-slate-500">Paciente</p>
                            <Link v-if="medicalhistories.patient" :href="route('patients.show', medicalhistories.patient.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                {{ medicalhistories.patient.name }} {{ medicalhistories.patient.last_name }}
                            </Link>
                            <span v-else class="text-slate-400">—</span>
                        </li>
                        <li>
                            <p class="text-slate-500">Médico</p>
                            <span class="font-semibold text-slate-800 dark:text-white">Dr. {{ medicalhistories.doctor?.name }} {{ medicalhistories.doctor?.last_name }}</span>
                        </li>
                        <li>
                            <p class="text-slate-500">Fármaco asociado</p>
                            <Link v-if="medicalhistories.drug" :href="route('drugs.show', medicalhistories.drug.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                {{ medicalhistories.drug.name }}
                            </Link>
                            <span v-else class="text-slate-400">—</span>
                        </li>
                    </ul>
                </section>
            </aside>
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
        medicalhistories: {
            type: Object,
            required: true,
        },
    },
};
</script>
