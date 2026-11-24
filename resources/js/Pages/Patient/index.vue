<template>
    <Head title="Pacientes" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Pacientes"
                description="Pacientes registrados y atendidos por el personal autorizado."
            >
                <template #actions>
                    <Link :href="route('patients.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nuevo paciente
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <section class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <SearchInput
                    v-model="form.search"
                    placeholder="Buscar por nombre, DNI, correo o fecha"
                    input-id="patients-search"
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
                :rows="patients.data"
                :loading="loading"
                empty-title="No hay pacientes"
                empty-description="No hay pacientes que coincidan con los filtros seleccionados."
            >
                <template #cell-name="{ row }">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            {{ initials(row) }}
                        </span>
                        <div class="min-w-0">
                            <Link :href="route('patients.show', row.id)" class="block truncate font-semibold text-slate-800 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                {{ row.name }} {{ row.last_name }}
                            </Link>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ row.email }}</p>
                        </div>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="row.status
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20'
                        : 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20'">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" />
                        {{ row.status ? 'Activo' : 'Inactivo' }}
                    </span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle" @click="openDrawer(row)">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </button>
                        <Link :href="route('patients.history', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Historial">
                            <UiIcon name="clock" size="h-4 w-4" />
                        </Link>
                        <Link :href="route('patients.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                        <button
                            v-if="row.status"
                            type="button"
                            class="ui-btn-ghost px-2 py-1.5 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                            title="Desactivar"
                            @click="requestDeactivate(row)"
                        >
                            <UiIcon name="trash" size="h-4 w-4" />
                        </button>
                    </div>
                </template>

                <template #emptyActions>
                    <Link :href="route('patients.create')" class="ui-btn-primary">Registrar paciente</Link>
                </template>
            </DataTable>

            <Pagination :pagination="patients" :filters="activeFilters" />
        </div>

        <DetailDrawer :open="drawerOpen" title="Detalle del paciente" :subtitle="selected?.email ?? ''" @close="drawerOpen = false">
            <template v-if="selected">
                <dl class="space-y-4">
                    <div v-for="item in drawerFields" :key="item.label" class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">{{ item.label }}</dt>
                        <dd class="text-end text-sm font-medium text-slate-800 dark:text-white">{{ item.value }}</dd>
                    </div>
                </dl>
            </template>
            <template #footer>
                <div class="flex flex-wrap justify-end gap-2">
                    <Link :href="route('patients.history', selected?.id)" class="ui-btn-secondary">Historial</Link>
                    <Link :href="route('patients.edit', selected?.id)" class="ui-btn-primary">Editar paciente</Link>
                </div>
            </template>
        </DetailDrawer>

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
import FilterDropdown from '@/Components/FilterDropdown.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import DetailDrawer from '@/Components/DetailDrawer.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
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
        DetailDrawer,
        ConfirmDialog,
        UiIcon,
    },
    props: {
        patients: { type: Object, required: true },
        filters: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            loading: false,
            form: {
                search: this.filters.search ?? '',
                lastDays: this.filters.lastDays ?? '30',
            },
            selected: null,
            drawerOpen: false,
            confirm: {
                open: false,
                title: '',
                message: '',
                patientId: null,
            },
            columns: [
                { key: 'name', label: 'Paciente' },
                { key: 'DNI', label: 'DNI' },
                { key: 'date_of_birth', label: 'Nacimiento' },
                { key: 'insurance', label: 'Seguro' },
                { key: 'appointment_count', label: 'Citas', align: 'center' },
                { key: 'status', label: 'Estado' },
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
        drawerFields() {
            const patient = this.selected;
            if (!patient) return [];
            return [
                { label: 'Nombre', value: `${patient.name ?? ''} ${patient.last_name ?? ''}`.trim() || '—' },
                { label: 'DNI', value: patient.DNI || '—' },
                { label: 'Correo', value: patient.email || '—' },
                { label: 'Teléfono', value: patient.phone || '—' },
                { label: 'Nacimiento', value: this.formatDate(patient.date_of_birth) },
                { label: 'Seguro', value: patient.insurance || 'Sin seguro' },
                { label: 'Citas registradas', value: patient.appointment_count ?? 0 },
            ];
        },
    },
    methods: {
        submit() {
            this.loading = true;
            router.get(route('patients.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => { this.loading = false; },
            });
        },
        requestDeactivate(patient) {
            this.confirm = {
                open: true,
                title: 'Desactivar paciente',
                message: `¿Desactivar a ${patient.name} ${patient.last_name}? Su historial se conservará.`,
                patientId: patient.id,
            };
        },
        executeConfirm() {
            if (!this.confirm.patientId) return;
            this.loading = true;
            router.delete(route('patients.destroy', this.confirm.patientId), {
                preserveScroll: true,
                onFinish: () => {
                    this.loading = false;
                    this.confirm.open = false;
                },
            });
        },
        openDrawer(patient) {
            this.selected = patient;
            this.drawerOpen = true;
        },
        initials(patient) {
            return `${patient.name?.[0] ?? ''}${patient.last_name?.[0] ?? ''}`.toUpperCase() || '—';
        },
        formatDate(dateString) {
            return dateString ? String(dateString).slice(0, 10) : '—';
        },
    },
};
</script>
