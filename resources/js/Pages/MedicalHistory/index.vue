<template>
    <Head title="Historias médicas" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Historias médicas"
                description="Registros clínicos de los pacientes atendidos por el personal autorizado."
            />
        </template>

        <div class="space-y-5">
            <section class="ui-card p-4">
                <SearchInput
                    v-model="search"
                    placeholder="Buscar por paciente, diagnóstico o síntomas"
                    input-id="medical-history-search"
                    width="w-full"
                />
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    La búsqueda filtra los {{ medicalhistories.length }} registros cargados.
                </p>
            </section>

            <DataTable
                :columns="columns"
                :rows="filteredHistories"
                empty-title="No hay historias médicas"
                :empty-description="search ? 'Ningún registro coincide con la búsqueda.' : 'Todavía no hay registros clínicos.'"
            >
                <template #cell-id="{ row }">
                    <Link :href="route('medicalhistories.show', row.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                        #{{ row.id }}
                    </Link>
                </template>

                <template #cell-patient="{ row }">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            <UiIcon name="user" size="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-800 dark:text-white">
                                {{ fullName(row.patient) }}
                            </p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ row.patient?.DNI || 'Sin DNI' }}
                            </p>
                        </div>
                    </div>
                </template>

                <template #cell-doctor="{ row }">
                    Dr. {{ fullName(row.doctor) }}
                </template>

                <template #cell-diagnosis="{ row }">
                    <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20">
                        {{ row.diagnosis || '—' }}
                    </span>
                </template>

                <template #cell-symptoms="{ row }">
                    <span class="block max-w-xs truncate text-slate-500 dark:text-slate-400" :title="row.symptoms || ''">
                        {{ row.symptoms || '—' }}
                    </span>
                </template>

                <template #cell-updated_at="{ row }">
                    {{ formatDate(row.updated_at) }}
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <Link :href="route('medicalhistories.show', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </Link>
                        <Link :href="route('medicalhistories.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                    </div>
                </template>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import DataTable from '@/Components/DataTable.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        SearchInput,
        DataTable,
        UiIcon,
    },
    props: {
        medicalhistories: { type: Array, default: () => [] },
    },
    data() {
        return {
            search: '',
            columns: [
                { key: 'id', label: 'Registro' },
                { key: 'patient', label: 'Paciente' },
                { key: 'doctor', label: 'Médico' },
                { key: 'diagnosis', label: 'Diagnóstico' },
                { key: 'symptoms', label: 'Síntomas' },
                { key: 'updated_at', label: 'Actualización' },
                { key: 'actions', label: 'Acciones', align: 'right' },
            ],
        };
    },
    computed: {
        filteredHistories() {
            const term = this.search.trim().toLowerCase();
            if (!term) return this.medicalhistories;

            return this.medicalhistories.filter((history) => [
                history.patient?.name,
                history.patient?.last_name,
                history.patient?.DNI,
                history.doctor?.name,
                history.doctor?.last_name,
                history.diagnosis,
                history.symptoms,
                history.reason,
            ].some((value) => String(value ?? '').toLowerCase().includes(term)));
        },
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        formatDate(value) {
            if (!value) return '—';
            return String(value).slice(0, 10);
        },
    },
};
</script>
