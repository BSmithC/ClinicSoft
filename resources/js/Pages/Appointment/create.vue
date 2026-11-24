<template>
    <Head title="Nueva cita" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                title="Nueva cita"
                description="La disponibilidad se calcula en el servidor a partir de sesiones, descansos y bloqueos."
            />
        </template>

        <div class="grid gap-6 lg:grid-cols-4">
            <aside class="ui-card h-fit p-4 lg:sticky lg:top-6">
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Progreso</p>
                <ol class="space-y-1">
                    <li v-for="item in steps" :key="item.number" class="flex items-center gap-3 rounded-lg px-2 py-2" :class="step === item.number ? 'bg-brand-50 dark:bg-brand-500/10' : ''">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="step >= item.number ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
                        >
                            <UiIcon v-if="step > item.number" name="check" size="h-3.5 w-3.5" />
                            <template v-else>{{ item.number }}</template>
                        </span>
                        <span class="text-sm" :class="step === item.number ? 'font-semibold text-brand-700 dark:text-brand-300' : 'text-slate-500 dark:text-slate-400'">
                            {{ item.label }}
                        </span>
                    </li>
                </ol>
            </aside>

            <section class="ui-card p-6 lg:col-span-3">
                <form @submit.prevent="submit" class="space-y-6">
                    <div v-show="step === 1" class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">1. Seleccione el paciente</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">La agenda se filtrará para evitar solapamientos con sus citas activas.</p>
                        </div>
                        <FormField label="Paciente" name="patient" required :error="errors.patient_id">
                            <select id="patient" v-model="form.patient_id" class="ui-input">
                                <option disabled value="">Seleccione un paciente</option>
                                <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                    {{ patient.name }} {{ patient.last_name }}
                                </option>
                            </select>
                        </FormField>
                    </div>

                    <div v-show="step === 2" class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">2. Especialidad y médico</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Elija un médico para consultar sus sesiones publicadas.</p>
                        </div>
                        <FormField label="Especialidad" name="specialty" :error="errors.specialty_id">
                            <select id="specialty" v-model="form.specialty_id" class="ui-input">
                                <option value="">Todas</option>
                                <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                                    {{ specialty.name }}
                                </option>
                            </select>
                        </FormField>
                        <FormField label="Médico" name="doctor" required :error="errors.doctor_id">
                            <select id="doctor" v-model="form.doctor_id" class="ui-input">
                                <option disabled value="">Seleccione un médico</option>
                                <option v-for="doctor in doctorsForSpecialty" :key="doctor.id" :value="doctor.id">
                                    Dr. {{ doctor.name }} {{ doctor.last_name }}
                                </option>
                            </select>
                        </FormField>
                    </div>

                    <div v-show="step === 3" class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">3. Sesión disponible</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Las sesiones mostradas son las publicadas por el médico seleccionado.</p>
                        </div>
                        <FormField label="Filtrar por fecha" name="date">
                            <input id="date" v-model="form.date" type="date" class="ui-input" />
                        </FormField>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="session in sessionsForSelection"
                                :key="session.id"
                                type="button"
                                class="rounded-lg border p-3 text-start transition"
                                :class="selectedSession === session.id
                                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10'
                                    : 'border-slate-200 hover:border-brand-300 dark:border-slate-700 dark:hover:border-brand-700'"
                                @click="chooseSession(session)"
                            >
                                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ session.title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                    {{ session.consultation_minutes }} min · {{ session.capacity }} cupos
                                </p>
                            </button>
                            <p v-if="!sessionsForSelection.length" class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400 sm:col-span-2">
                                No hay sesiones publicadas para la selección actual. Un administrador o médico debe programarlas.
                            </p>
                        </div>
                        <InputError :message="errors.session_id" class="mt-1" />
                    </div>

                    <div v-show="step === 4" class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">4. Espacios disponibles</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Los horarios ocupados o bloqueados no se pueden seleccionar.</p>
                        </div>
                        <div v-if="chosenSession" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                            {{ chosenSession.title }} · {{ formatDate(chosenSession.date) }} · {{ formatTime(chosenSession.start_time) }}–{{ formatTime(chosenSession.end_time) }}
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button
                                v-for="slot in slots"
                                :key="slot.start"
                                type="button"
                                :disabled="!slot.available"
                                :title="slot.reason ?? ''"
                                class="rounded-lg border px-2 py-2 text-sm font-medium transition"
                                :class="selectedSlot && selectedSlot.start === slot.start
                                    ? 'border-brand-600 bg-brand-600 text-white'
                                    : slot.available
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-500 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300'
                                        : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800'"
                                @click="chooseSlot(slot)"
                            >
                                {{ formatTime(slot.start) }}–{{ formatTime(slot.end) }}
                                <span v-if="!slot.available" class="mt-0.5 block text-[10px] font-normal">{{ slot.reason }}</span>
                            </button>
                            <p v-if="!slots.length" class="col-span-full rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                Seleccione una sesión para ver sus espacios.
                            </p>
                        </div>
                        <FormField label="Horario" name="starttime" :error="errors.starttime">
                            <input :value="form.starttime ? `${formatTime(form.starttime)}–${formatTime(form.endtime)}` : ''" type="text" class="ui-input" readonly placeholder="Sin horario seleccionado" />
                        </FormField>
                    </div>

                    <div v-show="step === 5" class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">5. Confirme la cita</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Revise los datos antes de guardar.</p>
                        </div>
                        <dl class="grid gap-3 rounded-lg bg-slate-50 p-4 text-sm dark:bg-slate-800/60 sm:grid-cols-2">
                            <div><dt class="text-slate-500 dark:text-slate-400">Paciente</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ selectedPatient?.name }} {{ selectedPatient?.last_name }}</dd></div>
                            <div><dt class="text-slate-500 dark:text-slate-400">Médico</dt><dd class="font-semibold text-slate-800 dark:text-white">Dr. {{ selectedDoctor?.name }} {{ selectedDoctor?.last_name }}</dd></div>
                            <div><dt class="text-slate-500 dark:text-slate-400">Fecha</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ formatDate(form.date) }}</dd></div>
                            <div><dt class="text-slate-500 dark:text-slate-400">Horario</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ formatTime(form.starttime) }}–{{ formatTime(form.endtime) }}</dd></div>
                        </dl>
                        <FormField label="Motivo de la consulta" name="title" required :error="errors.title">
                            <input id="title" v-model="form.title" type="text" class="ui-input" placeholder="Motivo de la visita" />
                        </FormField>
                        <FormField label="Notas" name="notes">
                            <textarea id="notes" v-model="form.notes" rows="3" class="ui-input" />
                        </FormField>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Medicamento" name="drug">
                                <select id="drug" v-model="form.drug_id" class="ui-input">
                                    <option value="">Sin medicamento</option>
                                    <option v-for="drug in drugs" :key="drug.id" :value="drug.id">{{ drug.name }}</option>
                                </select>
                            </FormField>
                            <FormField label="Estado inicial" name="status">
                                <select id="status" v-model="form.status" class="ui-input">
                                    <option v-for="status in initialStatusOptions" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                            </FormField>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                        <Link :href="route('appointments.index')" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">
                            Cancelar
                        </Link>
                        <div class="flex gap-2">
                            <button v-if="step > 1" type="button" class="ui-btn-secondary" @click="previous">
                                <UiIcon name="arrow-left" size="h-4 w-4" />
                                Atrás
                            </button>
                            <button v-if="step < 5" type="button" class="ui-btn-primary" :disabled="!canContinue" @click="next">
                                Continuar
                                <UiIcon name="chevron-right" size="h-4 w-4" />
                            </button>
                            <button v-else type="submit" class="ui-btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
                                <UiIcon v-else name="check" size="h-4 w-4" />
                                Confirmar cita
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormField from '@/Components/FormField.vue';
import InputError from '@/Components/InputError.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        InputError,
        UiIcon,
    },
    props: {
        patients: { type: Array, default: () => [] },
        doctors: { type: Array, default: () => [] },
        doctorProfiles: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        sessions: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        availability: { type: Object, default: () => ({}) },
        availabilitySessions: { type: Array, default: () => [] },
        availabilitySlots: { type: Object, default: () => ({}) },
        blockedDates: { type: Array, default: () => [] },
    },
    data() {
        return {
            step: 1,
            submitting: false,
            selectedSession: null,
            steps: [
                { number: 1, label: 'Paciente' },
                { number: 2, label: 'Especialidad y médico' },
                { number: 3, label: 'Sesión y fecha' },
                { number: 4, label: 'Horario' },
                { number: 5, label: 'Confirmación' },
            ],
            form: {
                title: '',
                notes: '',
                patient_id: '',
                specialty_id: '',
                doctor_id: '',
                session_id: '',
                date: '',
                starttime: '',
                endtime: '',
                drug_id: '',
                status: 'pending',
                attended: false,
                active: true,
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
        initialStatusOptions() {
            const allowed = this.statuses.filter((status) => ['pending', 'confirmed'].includes(status.value));
            return allowed.length ? allowed : [
                { value: 'pending', label: 'Pendiente' },
                { value: 'confirmed', label: 'Confirmada' },
            ];
        },
        doctorsForSpecialty() {
            if (!this.form.specialty_id) return this.doctors;

            const allowed = this.doctorProfiles
                .filter((profile) => (profile.specialties ?? []).some((specialty) => specialty.id === Number(this.form.specialty_id)))
                .map((profile) => profile.user_id);

            return this.doctors.filter((doctor) => allowed.includes(doctor.id));
        },
        sessionsForSelection() {
            const source = this.availabilitySessions.length ? this.availabilitySessions : this.sessions;

            return source.filter((session) =>
                (!this.form.doctor_id || session.doctor_id === Number(this.form.doctor_id))
                && (!this.form.specialty_id || session.specialty_id === Number(this.form.specialty_id))
                && (!this.form.date || String(session.date).slice(0, 10) === this.form.date)
            );
        },
        slots() {
            return this.availabilitySlots[this.selectedSession] ?? [];
        },
        selectedSlot() {
            return this.slots.find((slot) => slot.start === this.form.starttime && slot.end === this.form.endtime);
        },
        canContinue() {
            if (this.step === 1) return Boolean(this.form.patient_id);
            if (this.step === 2) return Boolean(this.form.doctor_id);
            if (this.step === 3) return Boolean(this.form.session_id);
            if (this.step === 4) return Boolean(this.form.starttime && this.form.endtime);
            return true;
        },
        selectedDoctor() {
            return this.doctors.find((doctor) => doctor.id === Number(this.form.doctor_id));
        },
        selectedPatient() {
            return this.patients.find((patient) => patient.id === Number(this.form.patient_id));
        },
        chosenSession() {
            return this.sessionsForSelection.find((session) => session.id === this.selectedSession);
        },
    },
    watch: {
        'form.specialty_id': 'refreshAvailability',
        'form.doctor_id': 'resetSessionAndRefresh',
        'form.date': 'refreshAvailability',
        'form.patient_id': 'refreshAvailability',
    },
    methods: {
        fetchAvailability(extra = {}) {
            if (!this.form.doctor_id && !this.form.date && !extra.session_id) return;

            router.get(route('availability.index'), {
                doctor_id: this.form.doctor_id || undefined,
                specialty_id: this.form.specialty_id || undefined,
                date: this.form.date || undefined,
                patient_id: this.form.patient_id || undefined,
                ...extra,
            }, {
                only: ['availabilitySessions', 'availabilitySlots', 'blockedDates'],
                preserveState: true,
                preserveScroll: true,
            });
        },
        refreshAvailability() {
            this.fetchAvailability();
        },
        resetSessionAndRefresh() {
            this.form.session_id = '';
            this.form.starttime = '';
            this.form.endtime = '';
            this.selectedSession = null;
            this.fetchAvailability();
        },
        chooseSession(session) {
            this.selectedSession = session.id;
            this.form.session_id = session.id;
            this.form.date = String(session.date).slice(0, 10);
            this.form.specialty_id = this.form.specialty_id || session.specialty_id || '';
            this.form.starttime = '';
            this.form.endtime = '';
            this.fetchAvailability({ session_id: session.id, doctor_id: undefined, date: undefined, specialty_id: undefined });
        },
        chooseSlot(slot) {
            if (!slot.available) return;
            this.form.starttime = slot.start;
            this.form.endtime = slot.end;
        },
        next() {
            if (this.canContinue && this.step < 5) this.step += 1;
        },
        previous() {
            if (this.step > 1) this.step -= 1;
        },
        submit() {
            this.submitting = true;
            router.post(route('appointments.store'), this.form, {
                preserveScroll: true,
                onFinish: () => { this.submitting = false; },
            });
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
