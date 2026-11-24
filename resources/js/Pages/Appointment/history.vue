<template>
    <Head :title="`Historial de la cita #${appointment.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                :title="`Historial de la cita #${appointment.id}`"
                :description="`${fullName(appointment.patient)} · Dr. ${fullName(appointment.doctor)}`"
            >
                <template #actions>
                    <Link :href="route('appointments.show', appointment.id)" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver al detalle
                    </Link>
                </template>
            </PageHeader>
        </template>

        <DataTable
            :columns="columns"
            :rows="histories"
            empty-title="Sin movimientos"
            empty-description="Esta cita todavía no registra cambios de estado."
        >
            <template #cell-created_at="{ row }">
                {{ row.created_at }}
            </template>

            <template #cell-from_status="{ row }">
                <StatusBadge v-if="row.from_status" :status="row.from_status" />
                <span v-else class="text-slate-400 dark:text-slate-500">—</span>
            </template>

            <template #cell-to_status="{ row }">
                <StatusBadge :status="row.to_status" />
            </template>

            <template #cell-reschedule="{ row }">
                <span v-if="row.from_date || row.to_date" class="text-xs">
                    {{ formatDate(row.from_date) }} {{ formatTime(row.from_start) }}
                    → {{ formatDate(row.to_date) }} {{ formatTime(row.to_start) }}
                </span>
                <span v-else class="text-slate-400 dark:text-slate-500">—</span>
            </template>

            <template #cell-actor="{ row }">
                {{ row.actor?.name ?? 'Sistema' }}
            </template>

            <template #cell-reason="{ row }">
                {{ row.reason ?? '—' }}
            </template>
        </DataTable>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        DataTable,
        StatusBadge,
        UiIcon,
    },
    props: {
        appointment: { type: Object, required: true },
        histories: { type: Array, default: () => [] },
    },
    data() {
        return {
            columns: [
                { key: 'created_at', label: 'Fecha del cambio' },
                { key: 'from_status', label: 'Estado anterior' },
                { key: 'to_status', label: 'Estado nuevo' },
                { key: 'reschedule', label: 'Reprogramación' },
                { key: 'actor', label: 'Usuario' },
                { key: 'reason', label: 'Motivo' },
            ],
        };
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        formatDate(dateString) {
            if (!dateString) return '';
            return String(dateString).slice(0, 10);
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '';
        },
    },
};
</script>
