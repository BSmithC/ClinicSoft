<template>
    <Head title="Editar cita" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                :title="`Editar cita #${appointments.id}`"
                description="La reprogramación valida sesión, capacidad y solapamientos en el servidor."
            >
                <template #actions>
                    <StatusBadge :status="appointments.status" />
                </template>
            </PageHeader>
        </template>

        <section class="ui-card p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Paciente" name="patient" required :error="errors.patient_id">
                        <select id="patient" v-model="form.patient_id" class="ui-input">
                            <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                {{ patient.name }} {{ patient.last_name }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Médico" name="doctor" required :error="errors.doctor_id">
                        <select id="doctor" v-model="form.doctor_id" class="ui-input">
                            <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                Dr. {{ doctor.name }} {{ doctor.last_name }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Especialidad" name="specialty">
                        <select id="specialty" v-model="form.specialty_id" class="ui-input">
                            <option value="">Sin especialidad</option>
                            <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                                {{ specialty.name }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Sesión médica" name="session" :error="errors.session_id">
                        <select id="session" v-model="form.session_id" class="ui-input" @change="onSessionChange">
                            <option value="">Cita independiente</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">
                                {{ session.title }} · {{ formatDate(session.date) }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Fecha" name="date" required :error="errors.date">
                        <input id="date" v-model="form.date" type="date" class="ui-input" />
                    </FormField>

                    <FormField label="Horario" name="starttime" required :error="errors.starttime || errors.endtime">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-400">Inicio</span>
                                <input id="starttime" v-model="form.starttime" type="time" class="ui-input" />
                            </div>
                            <div>
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-400">Fin</span>
                                <input id="endtime" v-model="form.endtime" type="time" class="ui-input" />
                            </div>
                        </div>
                    </FormField>
                </div>

                <section v-if="slots.length" class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/50">
                    <div class="mb-2 flex items-center gap-2">
                        <UiIcon name="clock" size="h-4 w-4" class="text-brand-600 dark:text-brand-400" />
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Espacios de la sesión</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="slot in slots"
                            :key="slot.start"
                            type="button"
                            :disabled="!slot.available"
                            :title="slot.reason ?? ''"
                            class="rounded-lg border px-2 py-1 text-xs font-medium transition"
                            :class="form.starttime === slot.start
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : slot.available
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300'
                                    : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-900'"
                            @click="chooseSlot(slot)"
                        >
                            {{ formatTime(slot.start) }}–{{ formatTime(slot.end) }}
                        </button>
                    </div>
                </section>

                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Motivo" name="title" required :error="errors.title" class="sm:col-span-2">
                        <input id="title" v-model="form.title" type="text" class="ui-input" />
                    </FormField>

                    <FormField label="Medicamento" name="drug">
                        <select id="drug" v-model="form.drug_id" class="ui-input">
                            <option value="">Sin medicamento</option>
                            <option v-for="drug in drugs" :key="drug.id" :value="drug.id">{{ drug.name }}</option>
                        </select>
                    </FormField>

                    <FormField label="Estado" name="status" :error="errors.status">
                        <select id="status" v-model="form.status" class="ui-input">
                            <option value="">Sin cambio</option>
                            <option v-for="transition in transitions" :key="transition.value" :value="transition.value">
                                {{ transition.label }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Motivo del cambio" name="reason" class="sm:col-span-2">
                        <input id="reason" v-model="form.reason" type="text" class="ui-input" />
                    </FormField>

                    <FormField label="Notas" name="notes" class="sm:col-span-2">
                        <textarea id="notes" v-model="form.notes" rows="3" class="ui-input" />
                    </FormField>
                </div>

                <FormActions
                    :cancel-href="route('appointments.show', appointments.id)"
                    label="Guardar cambios"
                    :processing="processing"
                />
            </form>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormField from '@/Components/FormField.vue';
import FormActions from '@/Components/FormActions.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        FormActions,
        StatusBadge,
        UiIcon,
    },
    props: {
        appointments: { type: Object, required: true },
        patients: { type: Array, default: () => [] },
        doctors: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        sessions: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        transitions: { type: Array, default: () => [] },
        availability: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            processing: false,
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
            this.processing = true;
            router.put(route('appointments.update', this.appointments.id), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
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
