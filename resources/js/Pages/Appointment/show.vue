<template>
    <Head :title="`Cita #${appointment.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                :title="`Cita #${appointment.id}`"
                :description="`${fullName(appointment.patient)} · Dr. ${fullName(appointment.doctor)}`"
            >
                <template #actions>
                    <StatusBadge :status="appointment.status" />
                    <Link :href="route('appointments.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver
                    </Link>
                    <Link :href="route('appointments.edit', appointment.id)" class="ui-btn-secondary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                    <Link :href="route('appointments.history', appointment.id)" class="ui-btn-secondary">
                        <UiIcon name="clock" size="h-4 w-4" />
                        Historial
                    </Link>
                    <button
                        v-if="canCancel"
                        type="button"
                        class="ui-btn-secondary border-rose-200 text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-500/10"
                        @click="confirmCancel = true"
                    >
                        <UiIcon name="close" size="h-4 w-4" />
                        Cancelar cita
                    </button>
                </template>
            </PageHeader>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="ui-card p-6 lg:col-span-2">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Información de la cita</h2>
                <dl class="grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                    <div v-for="field in infoFields" :key="field.label" class="border-b border-slate-100 pb-4 last:border-0 dark:border-slate-800">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ field.label }}</dt>
                        <dd class="mt-1 font-medium text-slate-800 dark:text-white">{{ field.value }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="space-y-6">
                <section v-if="transitions.length" class="ui-card p-5">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cambiar estado</h2>
                    <form class="space-y-3" @submit.prevent="changeStatus">
                        <FormField label="Nuevo estado" name="status">
                            <select v-model="nextStatus" class="ui-input">
                                <option disabled value="">Seleccione una transición</option>
                                <option v-for="transition in transitions" :key="transition.value" :value="transition.value">
                                    {{ transition.label }}
                                </option>
                            </select>
                        </FormField>
                        <FormField label="Motivo" name="reason">
                            <input v-model="reason" type="text" class="ui-input" placeholder="Motivo (opcional)" />
                        </FormField>
                        <button type="submit" class="ui-btn-primary w-full" :disabled="!nextStatus">
                            Aplicar estado
                        </button>
                    </form>
                </section>
                <section v-else class="ui-card p-5">
                    <p class="text-sm text-slate-500 dark:text-slate-400">La cita está en un estado final; no admite más transiciones.</p>
                </section>

                <section class="ui-card p-5">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Últimos movimientos</h2>
                    <ul class="space-y-3 text-sm">
                        <li v-for="history in recentHistory" :key="history.id" class="border-b border-slate-100 pb-2 last:border-0 dark:border-slate-800">
                            <StatusBadge :status="history.to_status" />
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ history.created_at }} · {{ history.actor?.name ?? 'Sistema' }}
                            </p>
                        </li>
                        <li v-if="!recentHistory.length" class="text-slate-500 dark:text-slate-400">Sin movimientos registrados.</li>
                    </ul>
                </section>
            </aside>
        </div>

        <ConfirmDialog
            :open="confirmCancel"
            title="Cancelar cita"
            :message="`¿Cancelar la cita #${appointment.id}? El espacio quedará disponible.`"
            confirm-label="Cancelar cita"
            tone="danger"
            @cancel="confirmCancel = false"
            @confirm="cancelAppointment"
        />
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormField from '@/Components/FormField.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        StatusBadge,
        ConfirmDialog,
        UiIcon,
    },
    props: {
        appointment: { type: Object, required: true },
        statuses: { type: Array, default: () => [] },
        transitions: { type: Array, default: () => [] },
    },
    data() {
        return {
            nextStatus: '',
            reason: '',
            confirmCancel: false,
        };
    },
    computed: {
        statusValue() {
            const status = this.appointment.status;
            return status && typeof status === 'object' ? status.value : status || 'pending';
        },
        canCancel() {
            return !['cancelled', 'completed', 'no_show'].includes(this.statusValue);
        },
        recentHistory() {
            return (this.appointment.histories ?? []).slice(-4).reverse();
        },
        infoFields() {
            return [
                { label: 'Paciente', value: this.fullName(this.appointment.patient) },
                { label: 'Médico', value: `Dr. ${this.fullName(this.appointment.doctor)}` },
                { label: 'Fecha', value: this.formatDate(this.appointment.date) },
                { label: 'Horario', value: `${this.formatTime(this.appointment.starttime)}–${this.formatTime(this.appointment.endtime)}` },
                { label: 'Sesión', value: this.appointment.session?.title ?? 'Cita independiente' },
                { label: 'Especialidad', value: this.appointment.specialty?.name ?? this.appointment.session?.specialty?.name ?? 'No especificada' },
                { label: 'Medicamento', value: this.appointment.drug?.name ?? 'No especificado' },
                { label: 'Asistencia', value: this.appointment.attended ? 'Sí' : 'No' },
                { label: 'Motivo', value: this.appointment.title ?? '—' },
                { label: 'Notas', value: this.appointment.notes ?? '—' },
            ];
        },
    },
    methods: {
        changeStatus() {
            if (!this.nextStatus) return;
            router.post(route('appointments.status', this.appointment.id), {
                status: this.nextStatus,
                reason: this.reason || undefined,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.nextStatus = '';
                    this.reason = '';
                },
            });
        },
        cancelAppointment() {
            this.confirmCancel = false;
            router.delete(route('appointments.destroy', this.appointment.id), {
                data: { reason: this.reason || 'Cancelada desde el detalle de la cita.' },
                preserveScroll: true,
            });
        },
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return new Intl.DateTimeFormat('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(new Date(`${String(dateString).slice(0, 10)}T12:00:00`));
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '—';
        },
    },
};
</script>
