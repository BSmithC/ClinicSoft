<template>
    <Head title="Editar sesión médica" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Editar sesión #{{ session.id }}</h1>
                <p class="text-sm text-slate-500">Los descansos se administran desde el detalle de la sesión.</p>
            </div>
        </template>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="doctor_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Médico *</label>
                        <select id="doctor_id" v-model="form.doctor_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
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
                    </div>
                    <div class="sm:col-span-2">
                        <label for="title" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Título *</label>
                        <input id="title" v-model="form.title" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
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
                    </div>
                    <div>
                        <label for="consultation_minutes" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Minutos por cita *</label>
                        <input id="consultation_minutes" v-model="form.consultation_minutes" type="number" min="5" max="480" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <div>
                        <label for="capacity" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Capacidad *</label>
                        <input id="capacity" v-model="form.capacity" type="number" min="1" max="100" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Estado</label>
                        <select id="status" v-model="form.status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Notas</label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <Link :href="route('sessions.show', session.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancelar</Link>
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
        session: {
            type: Object,
            required: true,
        },
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
    },
    data() {
        return {
            form: {
                doctor_id: this.session.doctor_id,
                specialty_id: this.session.specialty_id ?? '',
                title: this.session.title ?? '',
                date: String(this.session.date ?? '').slice(0, 10),
                start_time: String(this.session.start_time ?? '').slice(0, 5),
                end_time: String(this.session.end_time ?? '').slice(0, 5),
                consultation_minutes: this.session.consultation_minutes,
                capacity: this.session.capacity,
                status: this.statusValue(this.session) ?? 'scheduled',
                notes: this.session.notes ?? '',
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
    },
    methods: {
        statusValue(session) {
            const status = session?.status;
            return status && typeof status === 'object' ? status.value : status;
        },
        submit() {
            router.put(route('sessions.update', this.session.id), this.form, { preserveScroll: true });
        },
    },
};
</script>
