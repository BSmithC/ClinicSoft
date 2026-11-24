<template>
    <Head title="Gestión de citas" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Operación"
                title="Gestión de citas"
                description="Citas registradas en el sistema, ordenadas por fecha."
            >
                <template #actions>
                    <Link :href="route('agenda.index')" class="ui-btn-secondary">
                        <UiIcon name="calendar" size="h-4 w-4" />
                        Ver agenda
                    </Link>
                    <Link :href="route('appointments.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nueva cita
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <section class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <SearchInput
                    v-model="filtersForm.search"
                    placeholder="Buscar por paciente, motivo o fecha"
                    input-id="appointments-search"
                    class="sm:flex-1"
                    width="w-full"
                    @search="applyFilters"
                />
                <FilterDropdown
                    v-model="filtersForm.lastDays"
                    name="lastDays"
                    label="Periodo"
                    placeholder="Todos los periodos"
                    :options="periodOptions"
                    width="w-full sm:w-52"
                    @update:model-value="applyFilters"
                />
                <button type="button" class="ui-btn-primary w-full sm:w-auto" :disabled="loading" @click="applyFilters">
                    <UiIcon name="search" size="h-4 w-4" />
                    Buscar
                </button>
            </section>

            <DataTable
                :columns="columns"
                :rows="appointments.data"
                :loading="loading"
                empty-title="No hay citas"
                empty-description="No hay citas que coincidan con los filtros seleccionados."
            >
                <template #cell-id="{ row }">
                    <Link :href="route('appointments.show', row.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                        #{{ row.id }}
                    </Link>
                    <p class="max-w-48 truncate text-xs text-slate-500 dark:text-slate-400">{{ row.session?.title ?? row.title ?? 'Consulta' }}</p>
                </template>

                <template #cell-patient="{ row }">
                    <span class="font-medium text-slate-800 dark:text-white">{{ fullName(row.patient) }}</span>
                </template>

                <template #cell-doctor="{ row }">
                    <span class="text-slate-600 dark:text-slate-300">Dr. {{ fullName(row.doctor) }}</span>
                </template>

                <template #cell-schedule="{ row }">
                    <span class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                        {{ formatDate(row.date) }} · {{ formatTime(row.starttime) }}–{{ formatTime(row.endtime) }}
                    </span>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle" @click="openDrawer(row)">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </button>
                        <Link :href="route('appointments.history', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Historial">
                            <UiIcon name="clock" size="h-4 w-4" />
                        </Link>
                        <Link :href="route('appointments.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                        <button
                            v-if="statusValue(row) !== 'cancelled'"
                            type="button"
                            class="ui-btn-ghost px-2 py-1.5 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                            title="Cancelar"
                            @click="requestCancel(row)"
                        >
                            <UiIcon name="close" size="h-4 w-4" />
                        </button>
                    </div>
                </template>

                <template #emptyActions>
                    <Link :href="route('appointments.create')" class="ui-btn-primary">Registrar la primera cita</Link>
                </template>
            </DataTable>

            <section class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Cambio rápido de estado</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select
                        v-model="bulkAppointmentId"
                        class="ui-input w-full py-2 text-sm sm:w-40"
                        aria-label="Seleccionar cita"
                    >
                        <option value="">Seleccionar cita…</option>
                        <option v-for="appointment in appointments.data" :key="appointment.id" :value="appointment.id">
                            #{{ appointment.id }} · {{ fullName(appointment.patient) }}
                        </option>
                    </select>
                    <select
                        v-model="bulkStatus"
                        class="ui-input w-full py-2 text-sm sm:w-48"
                        aria-label="Nuevo estado"
                        :disabled="!bulkAppointmentId"
                    >
                        <option value="">Nuevo estado…</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                    <button type="button" class="ui-btn-secondary w-full sm:w-auto" :disabled="!bulkAppointmentId || !bulkStatus || loading" @click="applyBulkStatus">
                        Aplicar
                    </button>
                </div>
            </section>

            <Pagination :pagination="appointments" :filters="activeFilters" />
        </div>

        <DetailDrawer :open="drawerOpen" :title="`Cita #${selected?.id ?? ''}`" :subtitle="selected?.session?.title ?? selected?.title ?? ''" @close="drawerOpen = false">
            <template v-if="selected">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Estado actual</p>
                    <StatusBadge :status="selected.status" />
                </div>
                <dl class="space-y-4">
                    <div v-for="item in drawerFields" :key="item.label" class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">{{ item.label }}</dt>
                        <dd class="text-end text-sm font-medium text-slate-800 dark:text-white">{{ item.value }}</dd>
                    </div>
                </dl>
            </template>
            <template #footer>
                <div class="flex flex-wrap justify-end gap-2">
                    <Link :href="route('appointments.history', selected?.id)" class="ui-btn-secondary">Historial</Link>
                    <Link :href="route('appointments.edit', selected?.id)" class="ui-btn-primary">Editar cita</Link>
                </div>
            </template>
        </DetailDrawer>

        <ConfirmDialog
            :open="confirm.open"
            title="Cancelar cita"
            :message="confirm.message"
            :processing="loading"
            confirm-label="Cancelar cita"
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
import StatusBadge from '@/Components/StatusBadge.vue';
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
        StatusBadge,
        DetailDrawer,
        ConfirmDialog,
        UiIcon,
    },
    props: {
        appointments: { type: Object, required: true },
        filters: { type: Object, default: () => ({}) },
        statuses: { type: Array, default: () => [] },
    },
    data() {
        return {
            loading: false,
            filtersForm: {
                search: this.filters.search ?? '',
                lastDays: this.filters.lastDays ?? '30',
            },
            selected: null,
            drawerOpen: false,
            bulkAppointmentId: '',
            bulkStatus: '',
            confirm: { open: false, message: '', appointmentId: null },
            columns: [
                { key: 'id', label: 'Cita' },
                { key: 'patient', label: 'Paciente' },
                { key: 'doctor', label: 'Médico' },
                { key: 'schedule', label: 'Fecha y hora' },
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
                Object.entries(this.filtersForm).filter(([, value]) => value !== '' && value !== null)
            );
        },
        drawerFields() {
            const appointment = this.selected;
            if (!appointment) return [];
            return [
                { label: 'Paciente', value: this.fullName(appointment.patient) },
                { label: 'Médico', value: `Dr. ${this.fullName(appointment.doctor)}` },
                { label: 'Fecha', value: this.formatDate(appointment.date) },
                { label: 'Horario', value: `${this.formatTime(appointment.starttime)} – ${this.formatTime(appointment.endtime)}` },
                { label: 'Sesión', value: appointment.session?.title ?? appointment.title ?? '—' },
            ];
        },
    },
    methods: {
        applyFilters() {
            this.loading = true;
            router.get(route('appointments.index'), this.filtersForm, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => { this.loading = false; },
            });
        },
        statusValue(appointment) {
            const status = appointment.status;
            return status && typeof status === 'object' ? status.value : status || 'pending';
        },
        requestCancel(appointment) {
            this.confirm = {
                open: true,
                appointmentId: appointment.id,
                message: `¿Cancelar la cita #${appointment.id}? El espacio quedará disponible.`,
            };
        },
        executeConfirm() {
            if (!this.confirm.appointmentId) return;
            this.loading = true;
            router.delete(route('appointments.destroy', this.confirm.appointmentId), {
                data: { reason: 'Cancelada desde el listado de citas.' },
                preserveScroll: true,
                onFinish: () => {
                    this.loading = false;
                    this.confirm.open = false;
                },
            });
        },
        applyBulkStatus() {
            if (!this.bulkAppointmentId || !this.bulkStatus) return;
            this.loading = true;
            router.post(route('appointments.status', this.bulkAppointmentId), {
                status: this.bulkStatus,
                reason: 'Actualización desde el listado de citas.',
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.bulkAppointmentId = '';
                    this.bulkStatus = '';
                },
                onFinish: () => { this.loading = false; },
            });
        },
        openDrawer(appointment) {
            this.selected = appointment;
            this.drawerOpen = true;
        },
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return new Intl.DateTimeFormat('es-ES', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }).format(new Date(`${String(dateString).slice(0, 10)}T12:00:00`));
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '—';
        },
    },
};
</script>
