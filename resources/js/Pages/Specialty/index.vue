<template>
    <Head title="Especialidades" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Especialidades"
                description="Catálogo clínico usado al programar sesiones y citas."
            >
                <template #actions>
                    <Link :href="route('specialties.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nueva especialidad
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <form class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center" @submit.prevent="submit">
                <SearchInput
                    v-model="form.search"
                    placeholder="Buscar por nombre o descripción"
                    input-id="specialties-search"
                    class="sm:flex-1"
                    width="w-full"
                    @search="submit"
                />
                <button type="submit" class="ui-btn-primary w-full sm:w-auto" :disabled="loading">
                    <UiIcon name="search" size="h-4 w-4" />
                    Buscar
                </button>
            </form>

            <DataTable
                :columns="columns"
                :rows="specialties.data"
                :loading="loading"
                empty-title="No hay especialidades"
                empty-description="No hay especialidades que coincidan con la búsqueda."
            >
                <template #cell-name="{ row }">
                    <div class="min-w-0">
                        <Link :href="route('specialties.show', row.id)" class="font-semibold text-slate-800 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                            {{ row.name }}
                        </Link>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ row.description || 'Sin descripción' }}</p>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="row.active
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20'
                        : 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20'">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" />
                        {{ row.active ? 'Activa' : 'Inactiva' }}
                    </span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <Link :href="route('specialties.show', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </Link>
                        <Link :href="route('specialties.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                        <button
                            v-if="row.active"
                            type="button"
                            class="ui-btn-ghost px-2 py-1.5 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                            title="Desactivar"
                            @click="requestDeactivate(row)"
                        >
                            <UiIcon name="trash" size="h-4 w-4" />
                        </button>
                    </div>
                </template>
            </DataTable>

            <Pagination :pagination="specialties" :filters="activeFilters" />
        </div>

        <ConfirmDialog
            :open="confirm.open"
            :title="confirm.title"
            :message="confirm.message"
            :processing="loading"
            confirm-label="Desactivar"
            @cancel="confirm.open = false"
            @confirm="executeConfirm"
        />
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        SearchInput,
        DataTable,
        Pagination,
        ConfirmDialog,
        UiIcon,
    },
    props: {
        specialties: { type: Object, required: true },
        filters: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            loading: false,
            form: { search: this.filters.search ?? '' },
            confirm: { open: false, title: '', message: '', specialtyId: null },
            columns: [
                { key: 'name', label: 'Especialidad' },
                { key: 'slug', label: 'Slug' },
                { key: 'doctors_count', label: 'Médicos', align: 'center' },
                { key: 'sessions_count', label: 'Sesiones', align: 'center' },
                { key: 'status', label: 'Estado' },
                { key: 'actions', label: 'Acciones', align: 'right' },
            ],
        };
    },
    computed: {
        activeFilters() {
            return this.form.search ? { search: this.form.search } : {};
        },
    },
    methods: {
        submit() {
            this.loading = true;
            router.get(route('specialties.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => { this.loading = false; },
            });
        },
        requestDeactivate(specialty) {
            this.confirm = {
                open: true,
                title: 'Desactivar especialidad',
                message: `¿Desactivar ${specialty.name}? No se eliminarán sus sesiones históricas.`,
                specialtyId: specialty.id,
            };
        },
        executeConfirm() {
            if (!this.confirm.specialtyId) return;
            this.loading = true;
            router.delete(route('specialties.destroy', this.confirm.specialtyId), {
                preserveScroll: true,
                onFinish: () => {
                    this.loading = false;
                    this.confirm.open = false;
                },
            });
        },
    },
};
</script>
