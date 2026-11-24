<template>
    <DetailDrawer
        :open="open"
        :title="isEditing ? (rescheduleOnly ? 'Reprogramar cita' : 'Editar cita') : 'Nueva cita'"
        :subtitle="isEditing ? `Cita #${appointmentId}` : 'Seleccione paciente, sesión y horario disponible'"
        @close="$emit('close')"
    >
        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Paciente" name="appointment-patient" required :error="errors.patient_id">
                <div class="relative">
                    <UiIcon name="search" size="h-4 w-4" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        id="appointment-patient-search"
                        type="search"
                        class="ui-input pl-9"
                        :value="patientSearch"
                        placeholder="Buscar por nombre o apellido"
                        @input="onPatientSearch"
                    />
                </div>
                <select id="appointment-patient" v-model="form.patient_id" class="ui-input mt-2" :disabled="processing || !allowed">
                    <option value="">Seleccione un paciente</option>
                    <option v-for="patient in filteredPatients" :key="patient.id" :value="patient.id">
                        {{ patient.name }} {{ patient.last_name }}
                    </option>
                </select>
            </FormField>

            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Especialidad" name="appointment-specialty" :error="errors.specialty_id">
                    <select
                        id="appointment-specialty"
                        v-model="form.specialty_id"
                        class="ui-input"
                        :disabled="processing || !allowed"
                    >
                        <option value="">Sin filtro de especialidad</option>
                        <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                            {{ specialty.name }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Médico" name="appointment-doctor" required :error="errors.doctor_id">
                    <select id="appointment-doctor" v-model="form.doctor_id" class="ui-input" :disabled="processing || !allowed">
                        <option value="">Seleccione un médico</option>
                        <option v-for="doctor in doctorOptions" :key="doctor.id" :value="String(doctor.value)">
                            Dr. {{ doctor.label }}
                        </option>
                    </select>
                </FormField>
            </div>

            <FormField label="Fecha" name="appointment-date" required :error="errors.date">
                <input
                    id="appointment-date"
                    v-model="form.date"
                    type="date"
                    class="ui-input"
                    :disabled="processing || !allowed"
                />
            </FormField>

            <FormField label="Sesión" name="appointment-session" :error="errors.session_id">
                <select id="appointment-session" v-model="form.session_id" class="ui-input" :disabled="processing || !allowed">
                    <option value="">Sin sesión (cita independiente)</option>
                    <option v-for="session in sessionOptions" :key="session.id" :value="session.id">
                        {{ session.title || 'Sesión clínica' }} · {{ formatShortDate(session.date) }}
                        {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }}
                    </option>
                </select>
            </FormField>

            <section>
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                        Horario disponible <span class="text-rose-500" aria-hidden="true">*</span>
                    </p>
                    <span v-if="loadingSlots" class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400" role="status">
                        <span class="h-3 w-3 animate-spin rounded-full border-2 border-brand-500 border-t-transparent" />
                        Consultando disponibilidad…
                    </span>
                </div>
                <div v-if="slots.length" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <button
                        v-for="slot in slots"
                        :key="`${slot.sessionId || 'free'}-${slot.start}-${slot.end}`"
                        type="button"
                        class="rounded-lg border px-2 py-2 text-xs font-semibold transition"
                        :class="[slotClass(slot), selectedSlotKey === slotKey(slot) ? 'ring-2 ring-brand-500 ring-offset-1 dark:ring-offset-slate-900' : '']"
                        :disabled="!slot.available || processing || !allowed"
                        @click="chooseSlot(slot)"
                    >
                        {{ formatTime(slot.start) }} – {{ formatTime(slot.end) }}
                        <span v-if="!slot.available" class="mt-0.5 block text-[10px] font-normal">{{ slot.reason }}</span>
                    </button>
                </div>
                <p v-else-if="!loadingSlots" class="rounded-lg border border-dashed border-slate-200 p-3 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Seleccione médico y fecha para consultar los horarios publicados.
                </p>
                <p v-if="slotError" class="mt-2 text-sm text-rose-600 dark:text-rose-400" role="alert">{{ slotError }}</p>
                <p v-if="blockedNotice" class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                    {{ blockedNotice }}
                </p>
                <InputError :message="errors.starttime" class="mt-2" />
                <p v-if="selectedSlotLabel" class="mt-2 text-xs font-semibold text-brand-700 dark:text-brand-300">
                    Horario elegido: {{ selectedSlotLabel }}
                </p>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Motivo de la cita" name="appointment-title" required :error="errors.title">
                    <input
                        id="appointment-title"
                        v-model="form.title"
                        type="text"
                        maxlength="255"
                        class="ui-input"
                        placeholder="Motivo de la consulta"
                        :disabled="processing || !allowed"
                    />
                </FormField>

                <FormField v-if="statusOptions.length" :label="isEditing ? 'Nuevo estado' : 'Estado inicial'" name="appointment-status" :error="errors.status">
                    <select id="appointment-status" v-model="form.status" class="ui-input" :disabled="processing || !allowed">
                        <option v-if="isEditing" value="">Sin cambio de estado</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                </FormField>
            </div>

            <FormField label="Observaciones" name="appointment-notes" :error="errors.notes">
                <textarea
                    id="appointment-notes"
                    v-model="form.notes"
                    rows="3"
                    class="ui-input"
                    placeholder="Notas clínicas o instrucciones"
                    :disabled="processing || !allowed"
                />
            </FormField>

            <p class="text-xs text-slate-500 dark:text-slate-400">
                Duración configurada: {{ slotMinutes }} min. La disponibilidad se valida nuevamente en el servidor.
            </p>
        </form>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button type="button" class="ui-btn-secondary" :disabled="processing" @click="$emit('close')">
                    Cancelar
                </button>
                <button type="button" class="ui-btn-primary" :disabled="!canSubmit || processing" @click="submit">
                    <span v-if="processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                    <UiIcon v-else name="check" size="h-4 w-4" />
                    {{ isEditing ? 'Guardar cambios' : 'Crear cita' }}
                </button>
            </div>
        </template>
    </DetailDrawer>
</template>

<script>
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import DetailDrawer from '@/Components/DetailDrawer.vue';
import FormField from '@/Components/FormField.vue';
import InputError from '@/Components/InputError.vue';
import UiIcon from '@/Components/UiIcon.vue';
import {
    formatShortDate,
    formatTime,
    minutesFromTime,
    normalizeDate,
    parseSlotDateTime,
    toTimeWithSeconds,
    todayISO,
} from '@/utils/calendar';

export default {
    name: 'AppointmentForm',
    components: { DetailDrawer, FormField, InputError, UiIcon },
    props: {
        open: { type: Boolean, default: false },
        initial: { type: Object, default: () => ({}) },
        patients: { type: Array, default: () => [] },
        doctors: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        sessions: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        availability: { type: Object, default: () => ({}) },
        isAdmin: { type: Boolean, default: false },
    },
    emits: ['close', 'saved'],
    data() {
        return {
            initializing: false,
            suppressContextWatchers: false,
            requestToken: 0,
            processing: false,
            loadingSlots: false,
            slotError: '',
            formErrors: {},
            patientSearch: '',
            remoteSessions: [],
            remoteBlockedDates: [],
            remoteAvailability: {},
            slotsBySession: {},
            form: this.emptyForm(),
        };
    },
    computed: {
        errors() {
            return { ...(this.$page.props.errors ?? {}), ...this.formErrors };
        },
        isEditing() {
            return this.initial?.type === 'appointment' || String(this.initial?.id ?? '').startsWith('appointment-');
        },
        rescheduleOnly() {
            return Boolean(this.initial?.reschedule);
        },
        appointmentId() {
            if (!this.isEditing) return null;
            return this.initial?.sourceId ?? String(this.initial?.id ?? '').replace('appointment-', '');
        },
        allowed() {
            if (!this.isEditing) return true;
            return this.initial?.can?.edit !== false;
        },
        filteredPatients() {
            const query = this.patientSearch.trim().toLocaleLowerCase('es');
            if (!query) return this.patients;
            return this.patients.filter((patient) =>
                `${patient.name ?? ''} ${patient.last_name ?? ''}`.toLocaleLowerCase('es').includes(query)
            );
        },
        doctorOptions() {
            let options = (this.doctors || []).map((doctor) => ({
                id: doctor.id,
                value: doctor.user_id ?? doctor.id,
                label: `${doctor.user?.name ?? ''} ${doctor.user?.last_name ?? ''}`.trim(),
            }));
            if (this.form.specialty_id) {
                const filtered = options.filter((option) => {
                    const doctor = this.doctors.find((item) => item.id === option.id);
                    return (doctor?.specialties ?? []).some((specialty) => String(specialty.id) === String(this.form.specialty_id));
                });
                if (filtered.length) options = filtered;
            }
            return options;
        },
        allSessions() {
            const merged = [...(this.remoteSessions || []), ...(this.sessions || [])];
            return merged.filter((session, index) => merged.findIndex((item) => String(item.id) === String(session.id)) === index);
        },
        sessionOptions() {
            const options = this.allSessions.filter((session) => {
                const doctor = session.doctor_id ?? session.doctorId ?? session.doctor?.id ?? session.user_id;
                if (this.form.doctor_id && String(doctor) !== String(this.form.doctor_id)) return false;
                if (this.form.date && normalizeDate(session.date) !== this.form.date) return false;
                if (this.form.specialty_id && String(session.specialty_id ?? '') !== String(this.form.specialty_id)) return false;
                return true;
            });
            if (this.form.session_id && !options.some((session) => String(session.id) === String(this.form.session_id))) {
                const selected = this.allSessions.find((session) => String(session.id) === String(this.form.session_id));
                if (selected) options.unshift(selected);
            }
            return options.sort((a, b) => `${a.date ?? ''}${a.start_time ?? ''}`.localeCompare(`${b.date ?? ''}${b.start_time ?? ''}`));
        },
        slots() {
            const grouped = this.form.session_id
                ? { [this.form.session_id]: this.slotsBySession[this.form.session_id] ?? [] }
                : this.slotsBySession;

            const seen = new Set();
            return Object.entries(grouped || {}).flatMap(([sessionId, list]) =>
                (list || [])
                    .map((slot) => ({ ...slot, sessionId }))
                    .filter((slot) => {
                        const key = this.slotKey(slot);
                        if (seen.has(key)) return false;
                        seen.add(key);
                        return true;
                    })
            ).sort((a, b) => String(a.start).localeCompare(String(b.start)));
        },
        selectedSlotKey() {
            if (!this.form.starttime || !this.form.endtime) return '';
            return `${toTimeWithSeconds(this.form.starttime)}|${toTimeWithSeconds(this.form.endtime)}`;
        },
        selectedSlotLabel() {
            const slot = this.slots.find((item) => this.slotKey(item) === this.selectedSlotKey);
            return slot ? `${formatTime(slot.start)} – ${formatTime(slot.end)}` : '';
        },
        statusOptions() {
            if (this.isEditing) {
                return Array.isArray(this.initial?.transitions) ? this.initial.transitions : [];
            }
            return (this.statuses || []).filter((status) => ['pending', 'confirmed'].includes(status.value));
        },
        slotMinutes() {
            return this.remoteAvailability?.slot_minutes ?? this.availability?.slot_minutes ?? 30;
        },
        blockedNotice() {
            const blocked = (this.remoteBlockedDates || []).some(
                (item) => normalizeDate(item.date) === this.form.date
            );
            return blocked ? 'La fecha seleccionada tiene bloqueos registrados para el médico.' : '';
        },
        canSubmit() {
            return Boolean(
                this.allowed &&
                this.form.patient_id &&
                this.form.doctor_id &&
                this.form.date &&
                this.form.starttime &&
                this.form.endtime &&
                this.form.title.trim()
            );
        },
    },
    watch: {
        open(value) {
            if (value) this.initialize();
        },
        'form.doctor_id'() {
            this.onContextChange();
        },
        'form.date'() {
            this.onContextChange();
        },
        'form.specialty_id'() {
            this.onContextChange();
        },
        'form.patient_id'() {
            this.onContextChange();
        },
        'form.session_id'(value) {
            this.onSessionChange(value);
        },
    },
    methods: {
        emptyForm() {
            return {
                patient_id: '',
                doctor_id: '',
                specialty_id: '',
                session_id: '',
                date: todayISO(),
                starttime: '',
                endtime: '',
                title: '',
                notes: '',
                status: '',
            };
        },
        initialize() {
            this.initializing = true;
            this.form = this.emptyForm();
            this.formErrors = {};
            this.slotError = '';
            this.loadingSlots = false;
            this.slotsBySession = {};
            this.remoteSessions = [];
            this.remoteBlockedDates = [];
            this.remoteAvailability = {};
            this.patientSearch = '';

            const initial = this.initial || {};
            if (this.isEditing) {
                this.form.patient_id = initial.patientId ?? '';
                this.form.doctor_id = initial.doctorId ?? '';
                this.form.specialty_id = initial.specialtyId ?? '';
                this.form.session_id = initial.sessionId ?? '';
                this.form.date = normalizeDate(initial.date) || todayISO();
                this.form.starttime = formatTime(initial.start);
                this.form.endtime = formatTime(initial.end);
                this.form.title = initial.reason || initial.title || '';
                this.form.notes = initial.notes || initial.reason || '';
                this.form.status = '';
                this.patientSearch = initial.patientSearch || '';
            } else {
                this.form.date = normalizeDate(initial.date) || todayISO();
                this.form.doctor_id = initial.doctorId ?? '';
                this.form.session_id = initial.sessionId ?? '';
                this.form.starttime = formatTime(initial.start);
                this.form.endtime = formatTime(initial.end);
                this.form.status = this.statusOptions[0]?.value ?? 'pending';
            }

            this.$nextTick(() => {
                this.initializing = false;
                this.fetchAvailability();
            });
        },
        onPatientSearch(event) {
            this.patientSearch = event.target.value;
            if (this.form.patient_id && !this.filteredPatients.some((patient) => String(patient.id) === String(this.form.patient_id))) {
                this.form.patient_id = '';
            }
        },
        onContextChange() {
            if (this.initializing || this.suppressContextWatchers) return;
            this.form.session_id = '';
            this.form.starttime = '';
            this.form.endtime = '';
            const allowedDoctors = this.doctorOptions.map((option) => String(option.value));
            if (this.form.doctor_id && allowedDoctors.length && !allowedDoctors.includes(String(this.form.doctor_id))) {
                this.form.doctor_id = '';
            }
            this.fetchAvailability();
        },
        onSessionChange(sessionId) {
            if (this.initializing) return;
            const session = this.allSessions.find((item) => String(item.id) === String(sessionId));
            if (session) {
                const doctor = session.doctor_id ?? session.doctorId ?? session.doctor?.id ?? session.user_id;
                const start = minutesFromTime(this.form.starttime);
                const sessionStart = minutesFromTime(session.start_time);
                const sessionEnd = minutesFromTime(session.end_time);

                this.suppressContextWatchers = true;
                if (!this.form.date && session.date) this.form.date = normalizeDate(session.date);
                if (!this.form.doctor_id && doctor) this.form.doctor_id = doctor;
                if (!this.form.specialty_id && session.specialty_id) this.form.specialty_id = session.specialty_id;
                this.$nextTick(() => { this.suppressContextWatchers = false; });

                if (start === null || sessionStart === null || start < sessionStart || start >= sessionEnd) {
                    this.form.starttime = '';
                    this.form.endtime = '';
                }
            }
            this.fetchAvailability();
        },
        fetchAvailability() {
            if (!this.open) return;
            const token = ++this.requestToken;
            const params = {};
            if (this.form.doctor_id) params.doctor_id = this.form.doctor_id;
            if (this.form.date) params.date = this.form.date;
            if (this.form.specialty_id) params.specialty_id = this.form.specialty_id;
            if (this.form.patient_id) params.patient_id = this.form.patient_id;
            if (this.form.session_id) params.session_id = this.form.session_id;
            if (this.isEditing && this.appointmentId) params.ignore_id = this.appointmentId;

            // The availability endpoint needs a doctor (or a session) to apply
            // its scope. Wait for the user to pick one instead of firing a
            // request that would always fail with a validation error.
            if (!this.form.doctor_id && !this.form.session_id) {
                this.slotsBySession = {};
                this.remoteSessions = [];
                this.remoteBlockedDates = [];
                this.remoteAvailability = {};
                this.slotError = '';
                this.loadingSlots = false;
                return;
            }

            if (!Object.keys(params).length) {
                this.slotsBySession = {};
                this.remoteSessions = [];
                this.loadingSlots = false;
                return;
            }

            this.loadingSlots = true;
            this.slotError = '';

            axios.get(route('availability.index'), {
                params,
                headers: { Accept: 'application/json' },
            })
                .then((response) => {
                    if (token !== this.requestToken) return;
                    const data = response.data ?? {};
                    this.remoteSessions = Array.isArray(data.sessions) ? data.sessions : [];
                    this.slotsBySession = data.slots && typeof data.slots === 'object' ? data.slots : {};
                    this.remoteBlockedDates = Array.isArray(data.blockedDates) ? data.blockedDates : [];
                    this.remoteAvailability = data.availability && typeof data.availability === 'object' ? data.availability : {};
                })
                .catch(() => {
                    if (token === this.requestToken) {
                        this.slotsBySession = {};
                        this.slotError = 'No se pudo consultar la disponibilidad. Intente nuevamente.';
                    }
                })
                .finally(() => {
                    if (token === this.requestToken) this.loadingSlots = false;
                });
        },
        sessionFor(sessionId) {
            return this.allSessions.find((session) => String(session.id) === String(sessionId));
        },
        slotKey(slot) {
            return `${toTimeWithSeconds(slot.start)}|${toTimeWithSeconds(slot.end)}`;
        },
        slotClass(slot) {
            if (slot.available) {
                return 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-500 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300';
            }
            if (['occupied', 'booked', 'appointment'].includes(slot.reasonCode)) {
                return 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300';
            }
            return 'cursor-not-allowed border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900 dark:bg-rose-500/10 dark:text-rose-300';
        },
        chooseSlot(slot) {
            if (!slot.available || !this.allowed) return;
            if (!this.form.session_id && slot.sessionId) {
                this.form.session_id = slot.sessionId;
            }
            this.form.starttime = formatTime(slot.start);
            this.form.endtime = formatTime(slot.end);
        },
        submit() {
            if (!this.canSubmit) return;
            this.formErrors = {};
            this.processing = true;

            const parsed = parseSlotDateTime(this.form.date, this.form.starttime, this.form.endtime, this.slotMinutes);
            if (!parsed) {
                this.formErrors = { date: 'Seleccione una fecha y un horario válidos.' };
                this.processing = false;
                return;
            }

            // active y attended se gestionan en el servidor: nunca se envían desde el calendario.
            const payload = {
                from_calendar: true,
                title: this.form.title.trim(),
                notes: this.form.notes || null,
                doctor_id: this.form.doctor_id,
                patient_id: this.form.patient_id,
                specialty_id: this.form.specialty_id || null,
                session_id: this.form.session_id || null,
                date: parsed.date,
                starttime: parsed.start,
                endtime: parsed.end || parsed.start,
            };
            if (this.form.status) payload.status = this.form.status;

            const options = {
                preserveScroll: true,
                onSuccess: () => this.$emit('saved'),
                onError: (errors) => { this.formErrors = errors ?? {}; },
                onFinish: () => { this.processing = false; },
            };

            if (this.isEditing) {
                router.put(route('appointments.update', this.appointmentId), payload, options);
            } else {
                router.post(route('appointments.store'), payload, options);
            }
        },
        formatTime,
        formatShortDate,
    },
};
</script>
