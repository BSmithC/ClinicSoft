<template>
    <Head title="Nueva cita" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Nueva cita</h1>
            <p class="text-sm text-slate-500">La disponibilidad se calcula en el servidor a partir de sesiones, descansos y bloqueos.</p>
        </template>

        <div class="grid gap-6 lg:grid-cols-4">
            <aside class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                <ol class="space-y-3">
                    <li v-for="item in steps" :key="item.number" class="flex items-center gap-3">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                            :class="step >= item.number ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-700'"
                        >{{ item.number }}</span>
                        <span class="text-sm" :class="step === item.number ? 'font-semibold text-slate-800 dark:text-white' : 'text-slate-500'">{{ item.label }}</span>
                    </li>
                </ol>
            </aside>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-3">
                <form @submit.prevent="submit" class="space-y-6">
                    <div v-show="step === 1" class="space-y-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">1. Seleccione el paciente</h2>
                        <div>
                            <label for="patient" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Paciente *</label>
                            <select id="patient" v-model="form.patient_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                <option disabled value="">Seleccione un paciente</option>
                                <option v-for="patient in patients" :key="patient.id" :value="patient.id">{{ patient.name }} {{ patient.last_name }}</option>
                            </select>
                            <InputError :message="errors.patient_id" class="mt-1" />
                        </div>
                    </div>

                    <div v-show="step === 2" class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">2. Especialidad y médico</h2>
                        </div>
                        <div>
                            <label for="specialty" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Especialidad</label>
                            <select id="specialty" v-model="form.specialty_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                <option value="">Todas</option>
                                <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">{{ specialty.name }}</option>
                            </select>
                            <InputError :message="errors.specialty_id" class="mt-1" />
                        </div>
                        <div>
                            <label for="doctor" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Médico *</label>
                            <select id="doctor" v-model="form.doctor_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                <option disabled value="">Seleccione un médico</option>
                                <option v-for="doctor in doctorsForSpecialty" :key="doctor.id" :value="doctor.id">Dr. {{ doctor.name }} {{ doctor.last_name }}</option>
                            </select>
                            <InputError :message="errors.doctor_id" class="mt-1" />
                        </div>
                    </div>

                    <div v-show="step === 3" class="space-y-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">3. Sesión disponible</h2>
                        <div>
                            <label for="date" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Filtrar por fecha</label>
                            <input id="date" v-model="form.date" type="date" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="session in sessionsForSelection"
                                :key="session.id"
                                type="button"
                                class="rounded-lg border p-3 text-left transition-colors"
                                :class="selectedSession === session.id
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-950'
                                    : 'border-slate-200 hover:border-blue-300 dark:border-slate-700'"
                                @click="chooseSession(session)"
                            >
                                <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ session.title }}</p>
                                <p class="text-xs text-slate-500">{{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ session.consultation_minutes }} min · {{ session.capacity }} cupos</p>
                            </button>
                            <p v-if="!sessionsForSelection.length" class="text-sm text-slate-500 sm:col-span-2">
                                No hay sesiones publicadas para la selección actual. Un administrador o médico debe programarlas.
                            </p>
                        </div>
                        <InputError :message="errors.session_id" />
                    </div>

                    <div v-show="step === 4" class="space-y-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">4. Espacios disponibles</h2>
                        <div v-if="chosenSession" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ chosenSession.title }} · {{ formatDate(chosenSession.date) }} · {{ formatTime(chosenSession.start_time) }}–{{ formatTime(chosenSession.end_time) }}
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button
                                v-for="slot in slots"
                                :key="slot.start"
                                type="button"
                                :disabled="!slot.available"
                                :title="slot.reason ?? ''"
                                class="rounded-lg border px-2 py-2 text-sm font-medium transition-colors"
                                :class="selectedSlot && selectedSlot.start === slot.start
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : slot.available
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-500 dark:bg-emerald-950 dark:text-emerald-200'
                                        : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800'"
                                @click="chooseSlot(slot)"
                            >
                                {{ formatTime(slot.start) }}–{{ formatTime(slot.end) }}
                                <span v-if="!slot.available" class="block text-[10px]">{{ slot.reason }}</span>
                            </button>
                            <p v-if="!slots.length" class="col-span-full text-sm text-slate-500">Seleccione una sesión para ver sus espacios.</p>
                        </div>
                        <InputError :message="errors.starttime" />
                    </div>

                    <div v-show="step === 5" class="space-y-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">5. Confirme la cita</h2>
                        <dl class="grid gap-3 rounded-lg bg-slate-50 p-4 text-sm dark:bg-slate-800 sm:grid-cols-2">
                            <div><dt class="text-slate-500">Paciente</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ selectedPatient?.name }} {{ selectedPatient?.last_name }}</dd></div>
                            <div><dt class="text-slate-500">Médico</dt><dd class="font-semibold text-slate-800 dark:text-white">Dr. {{ selectedDoctor?.name }} {{ selectedDoctor?.last_name }}</dd></div>
                            <div><dt class="text-slate-500">Fecha</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ formatDate(form.date) }}</dd></div>
                            <div><dt class="text-slate-500">Horario</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ formatTime(form.starttime) }}–{{ formatTime(form.endtime) }}</dd></div>
                        </dl>
                        <div>
                            <label for="title" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Motivo de la consulta *</label>
                            <input id="title" v-model="form.title" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" placeholder="Motivo de la visita" />
                            <InputError :message="errors.title" class="mt-1" />
                        </div>
                        <div>
                            <label for="notes" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Notas</label>
                            <textarea id="notes" v-model="form.notes" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="drug" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Medicamento (opcional)</label>
                                <select id="drug" v-model="form.drug_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    <option value="">Sin medicamento</option>
                                    <option v-for="drug in drugs" :key="drug.id" :value="drug.id">{{ drug.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="status" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Estado inicial</label>
                                <select id="status" v-model="form.status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                    <option value="pending">Pendiente</option>
                                    <option value="confirmed">Confirmada</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                        <Link :href="route('appointments.index')" class="text-sm font-medium text-slate-600 hover:underline dark:text-slate-300">Cancelar</Link>
                        <div class="flex gap-2">
                            <button v-if="step > 1" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="previous">Atrás</button>
                            <button v-if="step < 5" type="button" :disabled="!canContinue" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" @click="next">Continuar</button>
                            <button v-else type="submit" :disabled="submitting" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Confirmar cita</button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        InputError,
    },
    props: {
        patients: {
            type: Array,
            default: () => [],
        },
        doctors: {
            type: Array,
            default: () => [],
        },
        doctorProfiles: {
            type: Array,
            default: () => [],
        },
        specialties: {
            type: Array,
            default: () => [],
        },
        sessions: {
            type: Array,
            default: () => [],
        },
        drugs: {
            type: Array,
            default: () => [],
        },
        statuses: {
            type: Array,
            default: () => [],
        },
        availability: {
            type: Object,
            default: () => ({}),
        },
        availabilitySessions: {
            type: Array,
            default: () => [],
        },
        availabilitySlots: {
            type: Object,
            default: () => ({}),
        },
        blockedDates: {
            type: Array,
            default: () => [],
        },
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
