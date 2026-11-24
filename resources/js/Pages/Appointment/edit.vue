<template>
    <Head title="Editar cita" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Editar cita #{{ appointments.id }}</h1>
            <p class="text-sm text-slate-500">La reprogramación valida sesión, capacidad y solapamientos en el servidor.</p>
        </template>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="patient" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Paciente *</label>
                        <select id="patient" v-model="form.patient_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option v-for="patient in patients" :key="patient.id" :value="patient.id">{{ patient.name }} {{ patient.last_name }}</option>
                        </select>
                        <InputError :message="errors.patient_id" class="mt-1" />
                    </div>
                    <div>
                        <label for="doctor" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Médico *</label>
                        <select id="doctor" v-model="form.doctor_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">Dr. {{ doctor.name }} {{ doctor.last_name }}</option>
                        </select>
                        <InputError :message="errors.doctor_id" class="mt-1" />
                    </div>
                    <div>
                        <label for="specialty" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Especialidad</label>
                        <select id="specialty" v-model="form.specialty_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option value="">Sin especialidad</option>
                            <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">{{ specialty.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="session" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Sesión médica</label>
                        <select id="session" v-model="form.session_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" @change="onSessionChange">
                            <option value="">Cita independiente</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.title }} · {{ formatDate(session.date) }}</option>
                        </select>
                        <InputError :message="errors.session_id" class="mt-1" />
                    </div>
                    <div>
                        <label for="date" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha *</label>
                        <input id="date" v-model="form.date" type="date" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.date" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="starttime" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Inicio *</label>
                            <input id="starttime" v-model="form.starttime" type="time" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        </div>
                        <div>
                            <label for="endtime" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Fin *</label>
                            <input id="endtime" v-model="form.endtime" type="time" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        </div>
                        <InputError :message="errors.starttime" class="mt-1 col-span-2" />
                    </div>
                </div>

                <div v-if="slots.length" class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Espacios de la sesión</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="slot in slots"
                            :key="slot.start"
                            type="button"
                            :disabled="!slot.available"
                            :title="slot.reason ?? ''"
                            class="rounded-lg border px-2 py-1 text-xs font-medium"
                            :class="form.starttime === slot.start
                                ? 'border-blue-600 bg-blue-600 text-white'
                                : slot.available
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200'
                                    : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-900'"
                            @click="chooseSlot(slot)"
                        >{{ formatTime(slot.start) }}–{{ formatTime(slot.end) }}</button>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="title" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Motivo *</label>
                        <input id="title" v-model="form.title" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.title" class="mt-1" />
                    </div>
                    <div>
                        <label for="drug" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Medicamento</label>
                        <select id="drug" v-model="form.drug_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option value="">Sin medicamento</option>
                            <option v-for="drug in drugs" :key="drug.id" :value="drug.id">{{ drug.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Estado</label>
                        <select id="status" v-model="form.status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option value="">Sin cambio</option>
                            <option v-for="transition in transitions" :key="transition.value" :value="transition.value">{{ transition.label }}</option>
                        </select>
                        <InputError :message="errors.status" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="reason" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Motivo del cambio</label>
                        <input id="reason" v-model="form.reason" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Notas</label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <Link :href="route('appointments.show', appointments.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancelar</Link>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Guardar cambios</button>
                </div>
            </form>
        </section>
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
        appointments: {
            type: Object,
            required: true,
        },
        patients: {
            type: Array,
            default: () => [],
        },
        doctors: {
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
        transitions: {
            type: Array,
            default: () => [],
        },
        availability: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            form: {
                title: this.appointments.title ?? '',
                notes: this.appointments.notes ?? '',
                patient_id: this.appointments.patient_id,
                doctor_id: this.appointments.doctor_id,
                specialty_id: this.appointments.specialty_id ?? '',
                session_id: this.appointments.session_id ?? '',
                date: String(this.appointments.date ?? '').slice(0, 10),
                starttime: String(this.appointments.starttime ?? '').slice(0, 5),
                endtime: String(this.appointments.endtime ?? '').slice(0, 5),
                drug_id: this.appointments.drug_id ?? '',
                status: '',
                reason: '',
                attended: Boolean(this.appointments.attended),
                active: Boolean(this.appointments.active),
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
        slots() {
            const slots = this.$page.props.availabilitySlots ?? {};
            return slots[this.form.session_id] ?? [];
        },
    },
    mounted() {
        this.fetchSlots();
    },
    watch: {
        'form.session_id': 'fetchSlots',
    },
    methods: {
        fetchSlots() {
            if (!this.form.session_id) return;

            router.get(route('availability.index'), {
                session_id: this.form.session_id,
                patient_id: this.form.patient_id,
            }, {
                only: ['availabilitySlots'],
                preserveState: true,
                preserveScroll: true,
            });
        },
        onSessionChange() {
            const session = this.sessions.find((item) => item.id === Number(this.form.session_id));

            if (session) {
                this.form.date = String(session.date).slice(0, 10);
                this.form.specialty_id = session.specialty_id ?? this.form.specialty_id;
                this.form.starttime = '';
                this.form.endtime = '';
            }

            this.fetchSlots();
        },
        chooseSlot(slot) {
            if (!slot.available) return;
            this.form.starttime = slot.start;
            this.form.endtime = slot.end;
        },
        submit() {
            router.put(route('appointments.update', this.appointments.id), this.form, {
                preserveScroll: true,
            });
        },
        formatDate(dateString) {
            if (!dateString) return '';
            return new Intl.DateTimeFormat('es-ES', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }).format(new Date(`${String(dateString).slice(0, 10)}T12:00:00`));
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '';
        },
    },
};
</script>
