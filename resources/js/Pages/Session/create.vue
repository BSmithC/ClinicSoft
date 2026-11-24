<template>
    <Head title="Nueva sesión médica" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Nueva sesión médica</h1>
                <p class="text-sm text-slate-500">El servidor valida horarios, descansos, bloqueos y solapamientos.</p>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="doctor_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Médico *</label>
                            <select id="doctor_id" v-model="form.doctor_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                <option disabled value="">Seleccione un médico</option>
                                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.user_id">Dr. {{ doctor.user?.name }} {{ doctor.user?.last_name }}</option>
                            </select>
                            <InputError :message="errors.doctor_id" class="mt-1" />
                        </div>
                        <div>
                            <label for="specialty_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Especialidad</label>
                            <select id="specialty_id" v-model="form.specialty_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                <option value="">Sin especialidad</option>
                                <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">{{ specialty.name }}</option>
                            </select>
                            <InputError :message="errors.specialty_id" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="title" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Título *</label>
                            <input id="title" v-model="form.title" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" placeholder="Consulta de cardiología" />
                            <InputError :message="errors.title" class="mt-1" />
                        </div>
                        <div>
                            <label for="date" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha *</label>
                            <input id="date" v-model="form.date" type="date" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            <InputError :message="errors.date" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="start_time" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Inicio *</label>
                                <input id="start_time" v-model="form.start_time" type="time" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            </div>
                            <div>
                                <label for="end_time" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Fin *</label>
                                <input id="end_time" v-model="form.end_time" type="time" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            </div>
                            <InputError :message="errors.start_time" class="mt-1 col-span-2" />
                            <InputError :message="errors.end_time" class="mt-1 col-span-2" />
                        </div>
                        <div>
                            <label for="consultation_minutes" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Minutos por cita *</label>
                            <input id="consultation_minutes" v-model="form.consultation_minutes" type="number" min="5" max="480" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            <InputError :message="errors.consultation_minutes" class="mt-1" />
                        </div>
                        <div>
                            <label for="capacity" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Capacidad *</label>
                            <input id="capacity" v-model="form.capacity" type="number" min="1" max="100" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            <InputError :message="errors.capacity" class="mt-1" />
                        </div>
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Estado</label>
                            <select id="status" v-model="form.status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Notas</label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                    </div>

                    <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-800 dark:text-white">Descansos</h2>
                            <button type="button" class="rounded-lg bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100" @click="addBreak">Agregar</button>
                        </div>
                        <div v-for="(breakItem, index) in form.breaks" :key="index" class="mb-2 grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
                            <input v-model="breakItem.start_time" type="time" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            <input v-model="breakItem.end_time" type="time" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            <input v-model="breakItem.reason" type="text" placeholder="Motivo" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                            <button type="button" class="text-xs font-semibold text-rose-600 hover:underline" @click="removeBreak(index)">Quitar</button>
                        </div>
                        <p v-if="!form.breaks.length" class="text-xs text-slate-500">Sin descansos. Puede agregarlos antes de guardar.</p>
                        <InputError :message="errors.breaks" class="mt-1" />
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                        <Link :href="route('sessions.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancelar</Link>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Programar sesión</button>
                    </div>
                </form>
            </section>

            <aside class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Fechas bloqueadas</h2>
                    <form @submit.prevent="blockDate" class="space-y-3">
                        <select v-model="blockForm.doctor_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option disabled value="">Médico</option>
                            <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.user_id">Dr. {{ doctor.user?.name }} {{ doctor.user?.last_name }}</option>
                        </select>
                        <input v-model="blockForm.date" type="date" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <input v-model="blockForm.reason" type="text" placeholder="Motivo del bloqueo" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <button type="submit" class="w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Bloquear fecha</button>
                    </form>
                    <ul class="mt-4 space-y-2 text-xs">
                        <li v-for="blocked in blockedDates" :key="`${blocked.doctor_id}-${blocked.date}`" class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 p-2 dark:bg-slate-800">
                            <span class="text-slate-600 dark:text-slate-300">{{ formatDate(blocked.date) }} · {{ doctorName(blocked.doctor_id) }}<br />{{ blocked.reason || 'Sin motivo' }}</span>
                            <button type="button" class="font-semibold text-rose-600 hover:underline" @click="unblock(blocked)">Quitar</button>
                        </li>
                        <li v-if="!blockedDates.length" class="text-slate-500">No hay fechas bloqueadas.</li>
                    </ul>
                </section>
            </aside>
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
        doctors: {
            type: Array,
            default: () => [],
        },
        specialties: {
            type: Array,
            default: () => [],
        },
        statuses: {
            type: Array,
            default: () => [],
        },
        blockedDates: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            form: {
                doctor_id: this.doctors.length === 1 ? this.doctors[0].user_id : '',
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
                doctor_id: this.doctors.length === 1 ? this.doctors[0].user_id : '',
                date: '',
                reason: '',
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
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
            router.post(route('sessions.store'), this.form, { preserveScroll: true });
        },
        blockDate() {
            router.post(route('blockedDates.store'), this.blockForm, {
                preserveScroll: true,
                onSuccess: () => {
                    this.blockForm.date = '';
                    this.blockForm.reason = '';
                },
            });
        },
        unblock(blocked) {
            if (!window.confirm(`¿Quitar el bloqueo del ${this.formatDate(blocked.date)}?`)) return;
            router.delete(route('blockedDates.destroy', blocked.id ?? `${blocked.doctor_id}-${blocked.date}`), { preserveScroll: true });
        },
        doctorName(doctorId) {
            return this.doctors.find((doctor) => doctor.user_id === Number(doctorId))?.user?.name ?? `Médico #${doctorId}`;
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return String(dateString).slice(0, 10);
        },
    },
};
</script>
