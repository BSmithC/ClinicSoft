<template>
    <Head :title="`Historial de ${patient.name} ${patient.last_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Historial de ${patient.name} ${patient.last_name}`"
                description="Solo personal autorizado."
            >
                <template #actions>
                    <Link :href="route('patients.show', patient.id)" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver a la ficha
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <section class="space-y-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Evolución de citas</h2>
                <DataTable
                    :columns="appointmentColumns"
                    :rows="appointments"
                    empty-title="Sin citas"
                    empty-description="No hay citas registradas para este paciente."
                >
                    <template #cell-id="{ row }">
                        <Link :href="route('appointments.show', row.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            #{{ row.id }}
                        </Link>
                    </template>
                    <template #cell-date="{ row }">
                        {{ formatDate(row.date) }} · {{ formatTime(row.starttime) }}
                    </template>
                    <template #cell-doctor="{ row }">
                        Dr. {{ fullName(row.doctor) }}
                    </template>
                    <template #cell-session="{ row }">
                        {{ row.session?.title ?? 'Independiente' }}
                    </template>
                    <template #cell-status="{ row }">
                        <StatusBadge :status="row.status" />
                    </template>
                    <template #cell-movements="{ row }">
                        <span v-for="history in row.histories" :key="history.id" class="me-2 inline-block text-xs">
                            {{ history.created_at }}: {{ history.to_status }}
                        </span>
                        <span v-if="!row.histories?.length" class="text-slate-400 dark:text-slate-500">—</span>
                    </template>
                </DataTable>
            </section>

            <section class="space-y-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Notas clínicas</h2>
                <DataTable
                    :columns="historyColumns"
                    :rows="medicalHistories"
                    empty-title="Sin notas clínicas"
                    empty-description="No hay notas registradas para este paciente."
                >
                    <template #cell-created_at="{ row }">
                        {{ formatDate(row.created_at) }}
                    </template>
                    <template #cell-diagnosis="{ row }">
                        {{ row.diagnosis || row.diagnosis_description || '—' }}
                    </template>
                    <template #cell-reason="{ row }">
                        {{ row.reason || '—' }}
                    </template>
                    <template #cell-symptoms="{ row }">
                        {{ row.symptoms || '—' }}
                    </template>
                    <template #cell-observation="{ row }">
                        {{ row.observation || row.description || '—' }}
                    </template>
                    <template #cell-vitals="{ row }">
                        {{ row.blood_pressure || '—' }} · {{ row.temperature || '—' }} °C ·
                        {{ row.weight || '—' }} kg · {{ row.heart_rate || '—' }} lpm
                    </template>
                </DataTable>
            </section>
        </div>
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
        patient: { type: Object, required: true },
        appointments: { type: Array, default: () => [] },
        medicalHistories: { type: Array, default: () => [] },
    },
    data() {
        return {
            appointmentColumns: [
                { key: 'id', label: 'Cita' },
                { key: 'date', label: 'Fecha' },
                { key: 'doctor', label: 'Médico' },
                { key: 'session', label: 'Sesión' },
                { key: 'status', label: 'Estado' },
                { key: 'movements', label: 'Movimientos' },
            ],
            historyColumns: [
                { key: 'created_at', label: 'Fecha' },
                { key: 'diagnosis', label: 'Diagnóstico' },
                { key: 'reason', label: 'Motivo' },
                { key: 'symptoms', label: 'Síntomas' },
                { key: 'observation', label: 'Observaciones' },
                { key: 'vitals', label: 'Constantes' },
            ],
        };
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return String(dateString).slice(0, 10);
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '—';
        },
    },
};
</script>
