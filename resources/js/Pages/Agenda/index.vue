<template>
    <Head title="Agenda general" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Calendario clínico"
                title="Agenda general"
                :description="isAdmin
                    ? 'Sesiones, citas y disponibilidad real de todo el equipo en el intervalo seleccionado.'
                    : 'Sesiones, citas y disponibilidad real de tu consulta en el intervalo seleccionado.'"
            />
        </template>

        <div class="space-y-5" :aria-busy="loading">
            <CalendarToolbar
                :view="view"
                :date="date"
                @new="openNewForm"
                @today="goToday"
                @prev="step(-1)"
                @next="step(1)"
                @update:date="setDate"
                @update:view="setView"
            />

            <CalendarFilters
                :filters="localFilters"
                :doctors="doctors"
                :specialties="specialties"
                :statuses="statuses"
                :is-admin="isAdmin"
                @update:filters="updateFilters"
            />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del intervalo">
                <StatCard v-for="card in summaryCards" :key="card.label" v-bind="card" />
            </section>

            <section class="ui-card relative overflow-hidden" :aria-busy="loading" aria-label="Calendario de citas">
                <LoadingState v-if="loading && !calendarEvents.length" :rows="6" :columns="5" label="Cargando agenda…" />

                <EmptyState
                    v-else-if="!calendarEvents.length && !availableSlotCount"
                    title="No hay eventos en este intervalo"
                    description="Pruebe otra fecha, ajuste los filtros o registre una nueva cita."
                    icon="calendar"
                >
                    <template #actions>
                        <button type="button" class="ui-btn-primary" @click="openNewForm">
                            <UiIcon name="plus" size="h-4 w-4" />
                            Nueva cita
                        </button>
                    </template>
                </EmptyState>

                <div v-else class="p-3 sm:p-4">
                    <AppointmentCalendar
                        :events="calendarEvents"
                        :sessions="sessions"
                        :availability-slots="availabilitySlots"
                        :blocked-dates="blockedDates"
                        :view="view"
                        :focus-date="date"
                        :day-summary="daySummary"
                        :timezone="timezone"
                        @select-date="onSelectDate"
                        @select-event="onSelectEvent"
                        @select-slot="onSelectSlot"
                    />
                </div>

                <div
                    v-if="loading"
                    class="pointer-events-none absolute inset-0 z-20 flex items-start justify-center bg-white/40 pt-16 backdrop-blur-[1px] dark:bg-slate-950/40"
                    role="status"
                >
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow dark:bg-slate-800 dark:text-slate-200">
                        <span class="h-3 w-3 animate-spin rounded-full border-2 border-brand-500 border-t-transparent" />
                        Actualizando agenda…
                    </span>
                </div>
            </section>
        </div>

        <AppointmentDrawer
            :open="drawerOpen"
            :appointment="selectedEvent"
            :statuses="statuses"
            :busy="actionBusy"
            @close="closeDrawer"
            @edit="openEditForm(false)"
            @reschedule="openEditForm(true)"
            @cancel="cancelAppointment"
            @history="openHistory"
            @change-status="changeStatus"
        />

        <AppointmentForm
            :open="formOpen"
            :initial="formInitial"
            :patients="patients"
            :doctors="doctors"
            :specialties="specialties"
            :sessions="sessions"
            :statuses="statuses"
            :availability="availability"
            :is-admin="isAdmin"
            @close="closeForm"
            @saved="onSaved"
        />
    </AuthenticatedLayout>
</template>

<script>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import LoadingState from '@/Components/LoadingState.vue';
import EmptyState from '@/Components/EmptyState.vue';
import UiIcon from '@/Components/UiIcon.vue';
import AppointmentCalendar from '@/Components/Calendar/AppointmentCalendar.vue';
import AppointmentDrawer from '@/Components/Calendar/AppointmentDrawer.vue';
import AppointmentForm from '@/Components/Calendar/AppointmentForm.vue';
import CalendarFilters from '@/Components/Calendar/CalendarFilters.vue';
import CalendarToolbar from '@/Components/Calendar/CalendarToolbar.vue';
import {
    addDays,
    addMonths,
    eachDay,
    formatTime,
    normalizeDate,
    rangeForView,
    summaryForDate,
    toTimeWithSeconds,
    todayISO,
} from '@/utils/calendar';

export default {
    name: 'AgendaIndex',
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        StatCard,
        LoadingState,
        EmptyState,
        UiIcon,
        AppointmentCalendar,
        AppointmentDrawer,
        AppointmentForm,
        CalendarFilters,
        CalendarToolbar,
    },
    props: {
        sessions: { type: Array, default: () => [] },
        appointments: { type: Array, default: () => [] },
        availabilitySlots: { type: Object, default: () => ({}) },
        blockedDates: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        doctors: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
        isAdmin: { type: Boolean, default: false },
        timezone: { type: String, default: '' },
        availability: { type: Object, default: () => ({}) },
        patients: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        events: { type: Array, default: () => [] },
        daySummary: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            loading: false,
            actionBusy: false,
            localView: ['month', 'week', 'day'].includes(this.filters.view) ? this.filters.view : 'month',
            localDate: normalizeDate(this.filters.date) || todayISO(),
            localFilters: {
                doctor_id: this.filters.doctor_id ?? '',
                specialty_id: this.filters.specialty_id ?? '',
                status: this.filters.status ?? '',
                patient: this.filters.patient ?? '',
            },
            drawerOpen: false,
            drawerEventId: null,
            formOpen: false,
            formInitial: null,
            patientFilterTimer: null,
        };
    },
    computed: {
        view() {
            return this.localView;
        },
        date() {
            return this.localDate;
        },
        calendarEvents() {
            if (this.events?.length) return this.events;
            return this.derivedEvents();
        },
        availableSlotCount() {
            return Object.values(this.availabilitySlots || {}).reduce(
                (total, slots) => total + (slots || []).filter((slot) => slot.available).length,
                0
            );
        },
        summaryCards() {
            const range = rangeForView(this.view, this.date);
            const totals = eachDay(range.from, range.to).reduce((carry, day) => {
                const summary = summaryForDate({
                    date: day,
                    events: this.calendarEvents,
                    sessions: this.sessions,
                    availabilitySlots: this.availabilitySlots,
                    blockedDates: this.blockedDates,
                    stored: this.daySummary?.[day] ?? null,
                });
                carry.appointments += summary.appointments;
                carry.sessions += summary.sessions;
                carry.free += summary.free;
                carry.blocked += summary.blocked;
                return carry;
            }, { appointments: 0, sessions: 0, free: 0, blocked: 0 });

            return [
                { label: 'Citas', value: totals.appointments, icon: 'calendar', tone: 'sky', hint: 'Registros en el intervalo' },
                { label: 'Sesiones', value: totals.sessions, icon: 'clipboard', tone: 'brand', hint: 'Bloques publicados' },
                { label: 'Horarios libres', value: totals.free, icon: 'check', tone: 'emerald', hint: 'Disponibilidad calculada' },
                { label: 'Bloqueos', value: totals.blocked, icon: 'close', tone: 'rose', hint: 'Fechas y descansos bloqueados' },
            ];
        },
        selectedEvent() {
            const event = this.calendarEvents.find((item) => item.id === this.drawerEventId);
            if (!event) return {};
            return {
                ...event,
                doctorName: this.doctorNameFor(event.doctorId),
                specialtyName: this.specialtyNameFor(event.specialtyId),
                sessionTitle: this.sessionTitleFor(event.sessionId),
            };
        },
    },
    watch: {
        filters: {
            immediate: true,
            deep: true,
            handler(value) {
                this.localView = ['month', 'week', 'day'].includes(value?.view) ? value.view : this.localView;
                this.localDate = normalizeDate(value?.date) || this.localDate;
                this.localFilters = {
                    doctor_id: value?.doctor_id ?? '',
                    specialty_id: value?.specialty_id ?? '',
                    status: value?.status ?? '',
                    patient: value?.patient ?? '',
                };
            },
        },
    },
    methods: {
        fullName(person) {
            return `${person?.name ?? ''} ${person?.last_name ?? ''}`.trim();
        },
        doctorNameFor(doctorId) {
            if (!doctorId) return '';
            const doctor = this.doctors.find((item) => String(item.user_id ?? item.id) === String(doctorId));
            return doctor ? `Dr. ${this.fullName(doctor.user)}` : `Médico #${doctorId}`;
        },
        specialtyNameFor(specialtyId) {
            if (!specialtyId) return '';
            return this.specialties.find((item) => String(item.id) === String(specialtyId))?.name ?? `Especialidad #${specialtyId}`;
        },
        sessionTitleFor(sessionId) {
            if (!sessionId) return '';
            return this.sessions.find((item) => String(item.id) === String(sessionId))?.title ?? `Sesión #${sessionId}`;
        },
        derivedEvents() {
            const events = [];

            (this.appointments || []).forEach((appointment) => {
                events.push({
                    id: `appointment-${appointment.id}`,
                    type: 'appointment',
                    sourceId: appointment.id,
                    date: normalizeDate(appointment.date),
                    start: toTimeWithSeconds(appointment.starttime ?? appointment.start),
                    end: toTimeWithSeconds(appointment.endtime ?? appointment.end),
                    title: appointment.title || this.fullName(appointment.patient) || `Cita #${appointment.id}`,
                    subtitle: appointment.session?.title || this.fullName(appointment.doctor) || '',
                    status: appointment.status,
                    specialtyId: appointment.specialty_id ?? appointment.session?.specialty_id ?? null,
                    doctorId: appointment.doctor_id ?? appointment.doctor?.id ?? null,
                    patientId: appointment.patient_id ?? appointment.patient?.id ?? null,
                    patientSearch: this.fullName(appointment.patient),
                    reason: appointment.reason || appointment.notes || '',
                    notes: appointment.notes || '',
                    can: appointment.can ?? {},
                    transitions: appointment.transitions ?? [],
                });
            });

            (this.sessions || []).forEach((session) => {
                events.push({
                    id: `session-${session.id}`,
                    type: 'session',
                    sourceId: session.id,
                    date: normalizeDate(session.date),
                    start: toTimeWithSeconds(session.start_time ?? session.start),
                    end: toTimeWithSeconds(session.end_time ?? session.end),
                    title: session.title || 'Sesión clínica',
                    subtitle: this.fullName(session.doctor) || '',
                    status: session.status,
                    specialtyId: session.specialty_id ?? null,
                    doctorId: session.doctor_id ?? session.doctor?.id ?? null,
                    patientId: null,
                    patientSearch: '',
                    reason: session.specialty?.name || '',
                    can: {},
                    transitions: [],
                });
            });

            (this.blockedDates || []).forEach((blocked) => {
                events.push({
                    id: `block-${blocked.id ?? blocked.date}`,
                    type: 'block',
                    sourceId: blocked.id ?? null,
                    date: normalizeDate(blocked.date),
                    start: toTimeWithSeconds(blocked.start ?? blocked.start_time) || null,
                    end: toTimeWithSeconds(blocked.end ?? blocked.end_time) || null,
                    title: blocked.reason || 'Fecha bloqueada',
                    subtitle: '',
                    status: null,
                    specialtyId: null,
                    doctorId: blocked.doctor_id ?? null,
                    patientId: null,
                    patientSearch: '',
                    reason: blocked.reason || '',
                    reasonCode: blocked.reasonCode || 'blocked',
                    can: {},
                    transitions: [],
                });
            });

            return events.sort((a, b) => `${a.date}${a.start ?? ''}`.localeCompare(`${b.date}${b.start ?? ''}`));
        },
        buildParams() {
            const range = rangeForView(this.localView, this.localDate);
            const params = {
                view: this.localView,
                date: this.localDate,
                from: range.from,
                to: range.to,
            };
            ['doctor_id', 'specialty_id', 'status', 'patient'].forEach((key) => {
                if (this.localFilters[key] !== '' && this.localFilters[key] !== null && this.localFilters[key] !== undefined) {
                    params[key] = this.localFilters[key];
                }
            });
            return params;
        },
        navigate() {
            this.loading = true;
            router.get(route('agenda.index'), this.buildParams(), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => { this.loading = true; },
                onFinish: () => { this.loading = false; },
            });
        },
        setView(value) {
            if (!['month', 'week', 'day'].includes(value) || value === this.localView) return;
            this.localView = value;
            this.navigate();
        },
        setDate(value) {
            const date = normalizeDate(value);
            if (!date || date === this.localDate) return;
            this.localDate = date;
            this.navigate();
        },
        goToday() {
            this.localDate = todayISO();
            this.navigate();
        },
        step(direction) {
            if (this.localView === 'month') {
                this.localDate = addMonths(this.localDate, direction);
            } else if (this.localView === 'week') {
                this.localDate = addDays(this.localDate, direction * 7);
            } else {
                this.localDate = addDays(this.localDate, direction);
            }
            this.navigate();
        },
        updateFilters(value) {
            this.localFilters = { ...value };
            const patientChanged = String(value.patient ?? '') !== String(this.filters.patient ?? '');

            if (patientChanged) {
                clearTimeout(this.patientFilterTimer);
                this.patientFilterTimer = setTimeout(() => this.navigate(), 300);
                return;
            }

            this.navigate();
        },
        onSelectDate(date) {
            const normalized = normalizeDate(date);
            if (!normalized) return;
            this.localView = 'day';
            this.localDate = normalized;
            this.navigate();
        },
        onSelectEvent(event) {
            if (event.type === 'appointment') {
                this.drawerEventId = event.id;
                this.drawerOpen = true;
                return;
            }
            if (event.type === 'session' && event.sourceId) {
                router.get(route('sessions.show', event.sourceId), {}, { preserveScroll: true });
            }
        },
        onSelectSlot(payload) {
            this.drawerOpen = false;
            this.formInitial = { ...payload };
            this.formOpen = true;
        },
        openNewForm() {
            this.drawerOpen = false;
            this.formInitial = {
                date: this.localDate,
                // Doctors only ever manage their own agenda; preselect it so
                // availability can be loaded immediately.
                doctorId: this.isAdmin ? '' : (this.filters.doctor_id ?? ''),
            };
            this.formOpen = true;
        },
        openEditForm(reschedule) {
            const event = this.selectedEvent;
            if (!event.sourceId || event.can?.edit === false) return;
            this.drawerOpen = false;
            this.formInitial = { ...event, reschedule: Boolean(reschedule) };
            this.formOpen = true;
        },
        closeDrawer() {
            this.drawerOpen = false;
            this.drawerEventId = null;
        },
        closeForm() {
            this.formOpen = false;
            this.formInitial = null;
        },
        onSaved() {
            this.closeForm();
            this.navigate();
        },
        cancelAppointment(reason) {
            const event = this.selectedEvent;
            if (!event.sourceId || event.can?.cancel === false) return;
            this.actionBusy = true;
            router.delete(route('appointments.destroy', event.sourceId), {
                from_calendar: true,
                reason: reason || null,
            }, {
                preserveScroll: true,
                onSuccess: () => this.closeDrawer(),
                onFinish: () => { this.actionBusy = false; },
            });
        },
        changeStatus(payload) {
            const event = this.selectedEvent;
            const allowed = (event.transitions ?? []).some((transition) => transition.value === payload.status);
            if (!event.sourceId || !allowed || event.can?.changeStatus === false) return;
            this.actionBusy = true;
            router.post(route('appointments.status', event.sourceId), {
                from_calendar: true,
                status: payload.status,
                reason: payload.reason || null,
            }, {
                preserveScroll: true,
                onFinish: () => { this.actionBusy = false; },
            });
        },
        openHistory() {
            const event = this.selectedEvent;
            if (!event.sourceId || event.can?.viewHistory === false) return;
            router.get(route('appointments.history', event.sourceId), {}, { preserveScroll: true });
        },
        formatTime,
    },
    beforeUnmount() {
        clearTimeout(this.patientFilterTimer);
    },
};
</script>
