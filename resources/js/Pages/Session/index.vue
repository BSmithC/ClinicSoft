<template>
    <Head title="Sesiones médicas" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Operación"
                title="Sesiones médicas"
                description="Bloques de atención con cupos, descansos y validación de solapamientos."
            >
                <template #actions>
                    <Link :href="route('sessions.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nueva sesión
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <form class="ui-card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submit">
                <FormField label="Fecha" name="session-date">
                    <input id="session-date" v-model="form.date" type="date" class="ui-input" />
                </FormField>
                <FormField label="Especialidad" name="session-specialty">
                    <FilterDropdown
                        v-model="form.specialty_id"
                        name="specialty_id"
                        label="Especialidad"
                        placeholder="Todas las especialidades"
                        :options="specialties.map(item => ({ value: item.id, label: item.name }))"
                        width="w-full"
                    />
                </FormField>
                <FormField label="Estado" name="session-status">
                    <FilterDropdown
                        v-model="form.status"
                        name="status"
                        label="Estado"
                        placeholder="Todos los estados"
                        :options="statuses.map(item => ({ value: item.value, label: item.label }))"
                        width="w-full"
                    />
                </FormField>
                <div class="flex items-end">
                    <button type="submit" class="ui-btn-primary w-full" :disabled="loading">
                        <UiIcon name="filter" size="h-4 w-4" />
                        Filtrar
                    </button>
                </div>
            </form>

            <DataTable
                :columns="columns"
                :rows="sessions.data"
                :loading="loading"
                empty-title="No hay sesiones"
                empty-description="No hay sesiones que coincidan con los filtros seleccionados."
            >
                <template #cell-title="{ row }">
                    <div class="min-w-0">
                        <Link :href="route('sessions.show', row.id)" class="font-semibold text-slate-800 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">
                            {{ row.title }}
                        </Link>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ row.specialty?.name ?? 'Sin especialidad' }}</p>
                    </div>
                </template>

                <template #cell-doctor="{ row }">
                    <span class="text-slate-600 dark:text-slate-300">Dr. {{ row.doctor?.name }} {{ row.doctor?.last_name }}</span>
                </template>

                <template #cell-schedule="{ row }">
                    <span class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                        {{ formatDate(row.date) }} · {{ formatTime(row.start_time) }}–{{ formatTime(row.end_time) }}
                    </span>
                </template>

                <template #cell-capacity="{ row }">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-600 dark:text-slate-300">{{ row.appointments_count }} / {{ row.capacity }}</span>
                        <span class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <span class="block h-full rounded-full bg-brand-500" :style="{ width: `${capacityPercent(row)}%` }" />
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">{{ row.consultation_minutes }} min por cita</p>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle" @click="openDrawer(row)">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </button>
                        <Link :href="route('sessions.show', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Abrir ficha">
                            <UiIcon name="clipboard" size="h-4 w-4" />
                        </Link>
                        <Link v-if="!isCancelled(row)" :href="route('sessions.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                        <button
                            v-if="!isCancelled(row)"
                            type="button"
                            class="ui-btn-ghost px-2 py-1.5 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                            title="Cancelar"
                            @click="requestCancel(row)"
                        >
                            <UiIcon name="close" size="h-4 w-4" />
                        </button>
                    </div>
                </template>
            </DataTable>

            <Pagination :pagination="sessions" :filters="activeFilters" />
        </div>

        <DetailDrawer :open="drawerOpen" :title="selected?.title ?? 'Detalle de sesión'" :subtitle="selected?.specialty?.name ?? ''" @close="drawerOpen = false">
            <template v-if="selected">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Estado</p>
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
                    <Link :href="route('sessions.show', selected?.id)" class="ui-btn-secondary">Abrir ficha completa</Link>
                    <Link v-if="selected && !isCancelled(selected)" :href="route('sessions.edit', selected.id)" class="ui-btn-primary">Editar sesión</Link>
                </div>
            </template>
        </DetailDrawer>

        <ConfirmDialog
            :open="confirm.open"
            title="Cancelar sesión"
            :message="confirm.message"
            :processing="loading"
            confirm-label="Cancelar sesión"
            @cancel="confirm.open = false"
            @confirm="executeConfirm"
        />
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormField from '@/Components/FormField.vue';
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
        FormField,
        FilterDropdown,
        DataTable,
        Pagination,
        StatusBadge,
        DetailDrawer,
        ConfirmDialog,
        UiIcon,
    },
    props: {
        sessions: { type: Object, required: true },
        specialties: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            loading: false,
            form: {
                date: this.filters.date ?? '',
                specialty_id: this.filters.specialty_id ?? '',
                status: this.filters.status ?? '',
            },
            selected: null,
            drawerOpen: false,
            confirm: { open: false, message: '', sessionId: null },
            columns: [
                { key: 'title', label: 'Sesión' },
                { key: 'doctor', label: 'Médico' },
                { key: 'schedule', label: 'Fecha y horario' },
                { key: 'capacity', label: 'Cupos' },
                { key: 'status', label: 'Estado' },
                { key: 'actions', label: 'Acciones', align: 'right' },
            ],
        };
    },
    computed: {
        activeFilters() {
            return Object.fromEntries(Object.entries(this.form).filter(([, value]) => value !== ''));
        },
        drawerFields() {
            const session = this.selected;
            if (!session) return [];
            return [
                { label: 'Médico', value: `Dr. ${session.doctor?.name ?? ''} ${session.doctor?.last_name ?? ''}`.trim() },
                { label: 'Especialidad', value: session.specialty?.name ?? '—' },
                { label: 'Fecha', value: this.formatDate(session.date) },
                { label: 'Horario', value: `${this.formatTime(session.start_time)} – ${this.formatTime(session.end_time)}` },
                { label: 'Cupos', value: `${session.appointments_count ?? 0} / ${session.capacity ?? 0}` },
                { label: 'Duración por cita', value: `${session.consultation_minutes ?? 0} min` },
            ];
        },
    },
    methods: {
        submit() {
            this.loading = true;
            router.get(route('sessions.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => { this.loading = false; },
            });
        },
        statusValue(session) {
            const status = session.status;
            return status && typeof status === 'object' ? status.value : status;
        },
        isCancelled(session) {
            return this.statusValue(session) === 'cancelled';
        },
        capacityPercent(session) {
            const capacity = Number(session.capacity || 0);
            if (!capacity) return 0;
            return Math.min(100, Math.round((Number(session.appointments_count || 0) / capacity) * 100));
        },
        requestCancel(session) {
            this.confirm = {
                open: true,
                sessionId: session.id,
                message: `¿Cancelar la sesión "${session.title}"? Las citas afectadas quedarán canceladas y registradas.`,
            };
        },
        executeConfirm() {
            if (!this.confirm.sessionId) return;
            this.loading = true;
            router.post(route('sessions.cancel', this.confirm.sessionId), {
                reason: 'Cancelada desde el listado de sesiones.',
            }, {
                preserveScroll: true,
                onFinish: () => {
                    this.loading = false;
                    this.confirm.open = false;
                },
            });
        },
        openDrawer(session) {
            this.selected = session;
            this.drawerOpen = true;
        },
        formatDate(dateString) {
            return dateString ? String(dateString).slice(0, 10) : '—';
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '—';
        },
    },
};
</script>
