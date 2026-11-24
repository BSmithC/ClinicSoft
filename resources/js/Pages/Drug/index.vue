<template>
    <Head title="Medicamentos" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Catálogo clínico"
                title="Medicamentos"
                description="Medicamentos disponibles para indicaciones y facturación."
            >
                <template #actions>
                    <Link :href="route('drugs.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nuevo medicamento
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <section class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <SearchInput
                    v-model="form.search"
                    placeholder="Buscar por nombre, descripción o vía"
                    input-id="drugs-search"
                    class="sm:flex-1"
                    width="w-full"
                    @search="submit"
                />
                <FilterDropdown
                    v-model="form.lastDays"
                    name="lastDays"
                    label="Periodo"
                    placeholder="Todos los periodos"
                    :options="periodOptions"
                    width="w-full sm:w-52"
                    @update:model-value="submit"
                />
                <button type="button" class="ui-btn-primary w-full sm:w-auto" :disabled="loading" @click="submit">
                    <UiIcon name="search" size="h-4 w-4" />
                    Buscar
                </button>
            </section>

            <DataTable
                :columns="columns"
                :rows="drugs.data"
                :loading="loading"
                empty-title="No hay medicamentos"
                empty-description="No hay medicamentos que coincidan con los filtros seleccionados."
            >
                <template #cell-name="{ row }">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            <UiIcon name="heart" size="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <Link :href="route('drugs.show', row.id)" class="block truncate font-semibold text-slate-800 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                {{ row.name }}
                            </Link>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">#{{ row.id }}</p>
                        </div>
                    </div>
                </template>

                <template #cell-description="{ row }">
                    <span class="block max-w-xs truncate text-slate-500 dark:text-slate-400" :title="row.description || ''">
                        {{ row.description || '—' }}
                    </span>
                </template>

                <template #cell-route="{ row }">
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ row.route || '—' }}
                    </span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <Link :href="route('drugs.show', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </Link>
                        <Link :href="route('drugs.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                    </div>
                </template>

                <template #emptyActions>
                    <Link :href="route('drugs.create')" class="ui-btn-primary">Registrar medicamento</Link>
                </template>
            </DataTable>

            <Pagination :pagination="drugs" :filters="activeFilters" />
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import FilterDropdown from '@/Components/FilterDropdown.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        SearchInput,
        FilterDropdown,
        DataTable,
        Pagination,
        UiIcon,
    },
    props: {
        drugs: { type: Object, required: true },
        filters: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            loading: false,
            form: {
                search: this.filters.search ?? '',
                lastDays: this.filters.lastDays ?? '30',
            },
            columns: [
                { key: 'name', label: 'Medicamento' },
                { key: 'description', label: 'Descripción' },
                { key: 'route', label: 'Vía' },
                { key: 'frequency', label: 'Frecuencia' },
                { key: 'duration', label: 'Duración' },
                { key: 'actions', label: 'Acciones', align: 'right' },
            ],
            periodOptions: [
                { value: '7', label: 'Últimos 7 días' },
                { value: '30', label: 'Últimos 30 días' },
                { value: 'month', label: 'Este mes' },
                { value: 'year', label: 'Este año' },
                { value: 'all', label: 'Todos' },
            ],
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
            this.loading = true;
            router.get(route('drugs.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => { this.loading = false; },
            });
        },
    },
};
</script>
