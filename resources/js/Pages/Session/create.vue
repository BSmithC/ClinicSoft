<template>
    <Head title="Nueva sesión médica" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                title="Nueva sesión médica"
                description="El servidor valida horarios, descansos, bloqueos y solapamientos."
            />
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="ui-card p-6 lg:col-span-2">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <FormField label="Médico" name="doctor_id" required :error="errors.doctor_id">
                            <select id="doctor_id" v-model="form.doctor_id" class="ui-input">
                                <option disabled value="">Seleccione un médico</option>
                                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.user_id">
                                    Dr. {{ fullName(doctor.user) }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Especialidad" name="specialty_id" :error="errors.specialty_id">
                            <select id="specialty_id" v-model="form.specialty_id" class="ui-input">
                                <option value="">Sin especialidad</option>
                                <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                                    {{ specialty.name }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Título" name="title" required :error="errors.title" class="sm:col-span-2">
                            <input id="title" v-model="form.title" type="text" class="ui-input" placeholder="Consulta de cardiología" />
                        </FormField>

                        <FormField label="Fecha" name="date" required :error="errors.date">
                            <input id="date" v-model="form.date" type="date" class="ui-input" />
                        </FormField>

                        <FormField
                            label="Horario"
                            name="start_time"
                            required
                            :error="errors.start_time || errors.end_time"
                        >
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-400">Inicio</span>
                                    <input id="start_time" v-model="form.start_time" type="time" class="ui-input" />
                                </div>
                                <div>
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-slate-400">Fin</span>
                                    <input id="end_time" v-model="form.end_time" type="time" class="ui-input" />
                                </div>
                            </div>
                        </FormField>

                        <FormField label="Minutos por cita" name="consultation_minutes" required :error="errors.consultation_minutes">
                            <input id="consultation_minutes" v-model="form.consultation_minutes" type="number" min="5" max="480" class="ui-input" />
                        </FormField>

                        <FormField label="Capacidad" name="capacity" required :error="errors.capacity">
                            <input id="capacity" v-model="form.capacity" type="number" min="1" max="100" class="ui-input" />
                        </FormField>

                        <FormField label="Estado" name="status">
                            <select id="status" v-model="form.status" class="ui-input">
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Notas" name="notes" class="sm:col-span-2">
                            <textarea id="notes" v-model="form.notes" rows="3" class="ui-input" />
                        </FormField>
                    </div>

                    <section class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800 dark:text-white">Descansos</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Se excluyen del cálculo de disponibilidad.</p>
                            </div>
                            <button type="button" class="ui-btn-secondary px-3 py-1.5 text-xs" @click="addBreak">
                                <UiIcon name="plus" size="h-3.5 w-3.5" />
                                Agregar
                            </button>
                        </div>

                        <div v-for="(breakItem, index) in form.breaks" :key="index" class="mb-2 grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
                            <input v-model="breakItem.start_time" type="time" class="ui-input" :aria-label="`Inicio del descanso ${index + 1}`" />
                            <input v-model="breakItem.end_time" type="time" class="ui-input" :aria-label="`Fin del descanso ${index + 1}`" />
                            <input v-model="breakItem.reason" type="text" placeholder="Motivo" class="ui-input" :aria-label="`Motivo del descanso ${index + 1}`" />
                            <button type="button" class="inline-flex items-center justify-center rounded-lg px-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10" @click="removeBreak(index)">
                                Quitar
                            </button>
                        </div>
                        <p v-if="!form.breaks.length" class="text-xs text-slate-500 dark:text-slate-400">
                            Sin descansos. Puede agregarlos antes de guardar.
                        </p>
                        <InputError :message="errors.breaks" class="mt-1" />
                    </section>

                    <FormActions
                        :cancel-href="route('sessions.index')"
                        label="Programar sesión"
                        :processing="processing"
                    />
                </form>
            </section>

            <aside class="space-y-6">
                <section class="ui-card p-5">
                    <div class="mb-3 flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                            <UiIcon name="close" size="h-4 w-4" />
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800 dark:text-white">Fechas bloqueadas</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Impiden programar sesiones y citas.</p>
                        </div>
                    </div>

                    <form class="space-y-3" @submit.prevent="blockDate">
                        <FormField label="Médico" name="block-doctor">
                            <select v-model="blockForm.doctor_id" class="ui-input">
                                <option disabled value="">Seleccione un médico</option>
                                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.user_id">
                                    Dr. {{ fullName(doctor.user) }}
                                </option>
                            </select>
                        </FormField>
                        <FormField label="Fecha" name="block-date">
                            <input v-model="blockForm.date" type="date" class="ui-input" />
                        </FormField>
                        <FormField label="Motivo" name="block-reason">
                            <input v-model="blockForm.reason" type="text" placeholder="Motivo del bloqueo" class="ui-input" />
                        </FormField>
                        <button type="submit" class="ui-btn-secondary w-full border-rose-200 text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-500/10" :disabled="blocking">
                            <UiIcon name="close" size="h-4 w-4" />
                            Bloquear fecha
                        </button>
                    </form>

                    <ul class="mt-4 space-y-2">
                        <li
                            v-for="blocked in blockedDates"
                            :key="`${blocked.doctor_id}-${blocked.date}`"
                            class="flex items-start justify-between gap-2 rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800/60"
                        >
                            <div class="min-w-0 text-xs">
                                <p class="font-semibold text-slate-700 dark:text-slate-200">
                                    {{ formatDate(blocked.date) }} · {{ doctorName(blocked.doctor_id) }}
                                </p>
                                <p class="truncate text-slate-500 dark:text-slate-400">{{ blocked.reason || 'Sin motivo' }}</p>
                            </div>
                            <button type="button" class="shrink-0 text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400" @click="askUnblock(blocked)">
                                Quitar
                            </button>
                        </li>
                        <li v-if="!blockedDates.length" class="rounded-lg border border-dashed border-slate-200 p-3 text-center text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            No hay fechas bloqueadas.
                        </li>
                    </ul>
                </section>
            </aside>
        </div>

        <ConfirmDialog
            :open="confirm.open"
            title="Quitar bloqueo"
            :message="confirmMessage"
            confirm-label="Quitar bloqueo"
            :processing="unblocking"
            @cancel="confirm.open = false"
            @confirm="unblock"
        />
    </AuthenticatedLayout>
</template>

<script>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormField from '@/Components/FormField.vue';
import FormActions from '@/Components/FormActions.vue';
import InputError from '@/Components/InputError.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        FormActions,
        InputError,
        ConfirmDialog,
        UiIcon,
    },
    props: {
        doctors: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        blockedDates: { type: Array, default: () => [] },
    },
    data() {
        const firstDoctor = this.doctors.length === 1 ? this.doctors[0].user_id : '';

        return {
            processing: false,
            blocking: false,
            unblocking: false,
            confirm: { open: false, item: null },
            form: {
                doctor_id: firstDoctor,
                specialty_id: '',
                title: '',
                date: '',
                start_time: '',
                end_time: '',
                consultation_minutes: 30,
                capacity: 10,
                status: 'scheduled',
                notes: '',
                breaks: [],
            },
            blockForm: {
                doctor_id: firstDoctor,
                date: '',
                reason: '',
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
        confirmMessage() {
            if (!this.confirm.item) return '';
            return `¿Quitar el bloqueo del ${this.formatDate(this.confirm.item.date)} para ${this.doctorName(this.confirm.item.doctor_id)}?`;
        },
    },
    methods: {
        addBreak() {
            this.form.breaks.push({ start_time: '', end_time: '', reason: '' });
        },
        removeBreak(index) {
            this.form.breaks.splice(index, 1);
        },
        submit() {
            this.processing = true;
            router.post(route('sessions.store'), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
        blockDate() {
            this.blocking = true;
            router.post(route('blockedDates.store'), this.blockForm, {
                preserveScroll: true,
                onFinish: () => { this.blocking = false; },
                onSuccess: () => {
                    this.blockForm.date = '';
                    this.blockForm.reason = '';
                },
            });
        },
        askUnblock(blocked) {
            this.confirm = { open: true, item: blocked };
        },
        unblock() {
            const blocked = this.confirm.item;
            if (!blocked) return;

            this.unblocking = true;
            router.delete(route('blockedDates.destroy', blocked.id ?? `${blocked.doctor_id}-${blocked.date}`), {
                preserveScroll: true,
                onFinish: () => {
                    this.unblocking = false;
                    this.confirm = { open: false, item: null };
                },
            });
        },
        doctorName(doctorId) {
            const doctor = this.doctors.find((item) => item.user_id === Number(doctorId));
            return doctor ? `Dr. ${this.fullName(doctor.user)}` : `Médico #${doctorId}`;
        },
        fullName(person) {
            if (!person) return 'Sin asignar';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || 'Sin asignar';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return String(dateString).slice(0, 10);
        },
    },
};
</script>
