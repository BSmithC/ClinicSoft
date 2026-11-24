<template>
    <Head title="Médicos" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Médicos"
                description="Perfiles clínicos registrados en el sistema."
            >
                <template #actions>
                    <Link v-if="canCreate" :href="route('doctors.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nuevo médico
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <section class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <SearchInput
                    v-model="form.search"
                    placeholder="Buscar por nombre o correo"
                    input-id="doctors-search"
                    class="sm:flex-1"
                    width="w-full"
                    @search="submit"
                />
                <FilterDropdown
                    v-model="form.active"
                    name="active"
                    label="Estado"
                    placeholder="Todos los estados"
                    :options="statusOptions"
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
                :rows="doctors.data"
                :loading="loading"
                empty-title="No hay médicos"
                empty-description="No hay perfiles que coincidan con los filtros seleccionados."
            >
                <template #cell-doctor="{ row }">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-xs font-bold text-sky-600 dark:bg-sky-500/10 dark:text-sky-300">
                            {{ initials(row.user) }}
                        </span>
                        <div class="min-w-0">
                            <Link :href="route('doctors.show', row.id)" class="block truncate font-semibold text-slate-800 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                                Dr. {{ row.user?.name }} {{ row.user?.last_name }}
                            </Link>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ row.user?.email }}</p>
                        </div>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="row.active
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20'
                        : 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20'">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" />
                        {{ row.active ? 'Activo' : 'Inactivo' }}
                    </span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle" @click="openDrawer(row)">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </button>
                        <Link :href="route('doctors.agenda', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Agenda">
                            <UiIcon name="calendar" size="h-4 w-4" />
                        </Link>
                        <Link v-if="canCreate" :href="route('doctors.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                        <button
                            v-if="canCreate && row.active"
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

            <Pagination :pagination="doctors" :filters="activeFilters" />
        </div>

        <DetailDrawer :open="drawerOpen" title="Detalle del médico" :subtitle="selected?.user?.email ?? ''" @close="drawerOpen = false">
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
                    <Link :href="route('doctors.agenda', selected?.id)" class="ui-btn-secondary">Ver agenda</Link>
                    <Link v-if="canCreate" :href="route('doctors.edit', selected?.id)" class="ui-btn-primary">Editar médico</Link>
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
        doctors: { type: Object, required: true },
        filters: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            loading: false,
            form: {
                search: this.filters.search ?? '',
                active: this.filters.active ?? '',
            },
            selected: null,
            drawerOpen: false,
            confirm: { open: false, title: '', message: '', doctorId: null },
            columns: [
                { key: 'doctor', label: 'Médico' },
                { key: 'license_number', label: 'Licencia' },
                { key: 'specialties', label: 'Especialidades' },
                { key: 'status', label: 'Estado' },
                { key: 'actions', label: 'Acciones', align: 'right' },
            ],
            statusOptions: [
                { value: '1', label: 'Activos' },
                { value: '0', label: 'Inactivos' },
            ],
        };
    },
    computed: {
        activeFilters() {
            return Object.fromEntries(Object.entries(this.form).filter(([, value]) => value !== ''));
        },
        canCreate() {
            return (this.$page.props.auth?.user?.roles ?? []).includes('admin');
        },
        drawerFields() {
            const doctor = this.selected;
            if (!doctor) return [];
            return [
                { label: 'Nombre', value: `Dr. ${doctor.user?.name ?? ''} ${doctor.user?.last_name ?? ''}`.trim() },
                { label: 'Correo', value: doctor.user?.email ?? '—' },
                { label: 'Licencia', value: doctor.license_number || '—' },
                { label: 'Especialidades', value: (doctor.specialties ?? []).map((item) => item.name).join(', ') || '—' },
                { label: 'Estado', value: doctor.active ? 'Activo' : 'Inactivo' },
            ];
        },
    },
    methods: {
        submit() {
            this.loading = true;
            router.get(route('doctors.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => { this.loading = false; },
            });
        },
        requestDeactivate(doctor) {
            this.confirm = {
                open: true,
                title: 'Desactivar perfil médico',
                message: `¿Desactivar el perfil de ${doctor.user?.name}? Su historial se conservará.`,
                doctorId: doctor.id,
            };
        },
        executeConfirm() {
            if (!this.confirm.doctorId) return;
            this.loading = true;
            router.delete(route('doctors.destroy', this.confirm.doctorId), {
                preserveScroll: true,
                onFinish: () => {
                    this.loading = false;
                    this.confirm.open = false;
                },
            });
        },
        openDrawer(doctor) {
            this.selected = doctor;
            this.drawerOpen = true;
        },
        initials(user) {
            return `${user?.name?.[0] ?? ''}${user?.last_name?.[0] ?? ''}`.toUpperCase() || '—';
        },
    },
};
</script>
