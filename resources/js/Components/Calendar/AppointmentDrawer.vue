<template>
    <DetailDrawer
        :open="open"
        :title="drawerTitle"
        :subtitle="drawerSubtitle"
        @close="$emit('close')"
    >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300">
                <UiIcon name="information" size="h-4 w-4" />
                Estado actual
            </div>
            <StatusBadge :status="event.status" />
        </div>

        <dl class="space-y-3">
            <div
                v-for="field in fields"
                :key="field.label"
                class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800"
            >
                <dt class="shrink-0 text-sm text-slate-500 dark:text-slate-400">{{ field.label }}</dt>
                <dd class="min-w-0 text-end text-sm font-medium text-slate-800 dark:text-white">
                    {{ field.value || '—' }}
                </dd>
            </div>
        </dl>

        <section v-if="allowed('changeStatus')" class="mt-6 rounded-lg border border-slate-200 p-4 dark:border-slate-700">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-white">
                <UiIcon name="refresh" size="h-4 w-4" />
                Cambiar estado
            </h3>
            <p v-if="!transitions.length" class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                El estado actual no permite cambios.
            </p>
            <div v-else class="mt-3 grid gap-3 sm:grid-cols-2">
                <FormField label="Nuevo estado" name="appointment-next-status">
                    <select id="appointment-next-status" v-model="nextStatus" class="ui-input" :disabled="busy">
                        <option v-for="transition in transitions" :key="transition.value" :value="transition.value">
                            {{ transition.label }}
                        </option>
                    </select>
                </FormField>
                <FormField label="Motivo (opcional)" name="appointment-status-reason">
                    <input
                        id="appointment-status-reason"
                        v-model="statusReason"
                        type="text"
                        maxlength="255"
                        class="ui-input"
                        placeholder="Motivo del cambio"
                        :disabled="busy"
                    />
                </FormField>
            </div>
            <button
                v-if="transitions.length"
                type="button"
                class="ui-btn-secondary mt-3 w-full"
                :disabled="busy || !nextStatus"
                @click="submitStatus"
            >
                <UiIcon name="check" size="h-4 w-4" />
                Actualizar estado
            </button>
        </section>

        <template #footer>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button type="button" class="ui-btn-ghost" @click="$emit('close')">Cerrar</button>
                <button
                    v-if="allowed('viewHistory')"
                    type="button"
                    class="ui-btn-secondary"
                    :disabled="busy"
                    @click="$emit('history')"
                >
                    <UiIcon name="history" size="h-4 w-4" />
                    Historial
                </button>
                <button
                    v-if="allowed('cancel')"
                    type="button"
                    class="ui-btn-secondary text-rose-600 dark:text-rose-400"
                    :disabled="busy"
                    @click="cancelDialog = true"
                >
                    <UiIcon name="close" size="h-4 w-4" />
                    Cancelar
                </button>
                <button
                    v-if="allowed('edit')"
                    type="button"
                    class="ui-btn-secondary"
                    :disabled="busy"
                    @click="$emit('edit')"
                >
                    <UiIcon name="pencil" size="h-4 w-4" />
                    Editar
                </button>
                <button
                    v-if="allowed('edit')"
                    type="button"
                    class="ui-btn-primary"
                    :disabled="busy"
                    @click="$emit('reschedule')"
                >
                    <UiIcon name="calendar-day" size="h-4 w-4" />
                    Reprogramar
                </button>
            </div>
        </template>

        <ConfirmDialog
            :open="cancelDialog"
            title="Cancelar cita"
            message="La cita se cancelará y el horario volverá a estar disponible."
            confirm-label="Sí, cancelar cita"
            :processing="busy"
            @cancel="cancelDialog = false"
            @confirm="confirmCancel"
        >
            <FormField label="Motivo de la cancelación" name="appointment-cancel-reason">
                <textarea
                    id="appointment-cancel-reason"
                    v-model="cancelReason"
                    rows="2"
                    maxlength="255"
                    class="ui-input"
                    placeholder="Explique brevemente el motivo"
                />
            </FormField>
        </ConfirmDialog>
    </DetailDrawer>
</template>

<script>
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import DetailDrawer from '@/Components/DetailDrawer.vue';
import FormField from '@/Components/FormField.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UiIcon from '@/Components/UiIcon.vue';
import { formatLongDate, formatTime, timeRangeLabel } from '@/utils/calendar';

export default {
    name: 'AppointmentDrawer',
    components: { ConfirmDialog, DetailDrawer, FormField, StatusBadge, UiIcon },
    props: {
        open: { type: Boolean, default: false },
        appointment: { type: Object, default: () => ({}) },
        statuses: { type: Array, default: () => [] },
        busy: { type: Boolean, default: false },
    },
    emits: ['close', 'edit', 'reschedule', 'cancel', 'history', 'change-status'],
    data() {
        return {
            nextStatus: '',
            statusReason: '',
            cancelReason: '',
            cancelDialog: false,
        };
    },
    computed: {
        event() {
            return this.appointment || {};
        },
        transitions() {
            return Array.isArray(this.event.transitions) ? this.event.transitions : [];
        },
        drawerTitle() {
            return `Cita #${this.event.sourceId ?? ''}`;
        },
        drawerSubtitle() {
            return `${formatLongDate(this.event.date)} · ${timeRangeLabel(this.event.start, this.event.end)}`;
        },
        fields() {
            return [
                { label: 'Paciente', value: this.event.patientSearch || this.event.patientId || '' },
                { label: 'Médico', value: this.event.doctorName || this.event.subtitle || '' },
                { label: 'Especialidad', value: this.event.specialtyName || '' },
                { label: 'Inicio', value: formatTime(this.event.start) },
                { label: 'Fin', value: formatTime(this.event.end) },
                { label: 'Sesión', value: this.event.sessionTitle || this.event.sessionId || '' },
                { label: 'Observaciones', value: this.event.notes || this.event.reason || 'Sin observaciones registradas' },
            ];
        },
    },
    watch: {
        appointment: {
            immediate: true,
            handler() {
                this.nextStatus = this.transitions[0]?.value ?? '';
                this.statusReason = '';
                this.cancelReason = '';
                this.cancelDialog = false;
            },
        },
    },
    methods: {
        allowed(key) {
            return this.event.can?.[key] !== false;
        },
        submitStatus() {
            if (!this.nextStatus || this.busy) return;
            this.$emit('change-status', { status: this.nextStatus, reason: this.statusReason.trim() });
            this.statusReason = '';
        },
        confirmCancel() {
            if (this.busy) return;
            const reason = this.cancelReason.trim();
            this.cancelDialog = false;
            this.$emit('cancel', reason);
        },
    },
};
</script>
