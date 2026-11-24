<template>
    <Head title="Editar sesión médica" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                :title="`Editar sesión #${session.id}`"
                description="Los descansos se administran desde el detalle de la sesión."
            />
        </template>

        <section class="ui-card p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField label="Médico" name="doctor_id" required :error="errors.doctor_id">
                        <select id="doctor_id" v-model="form.doctor_id" class="ui-input">
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
                        <input id="title" v-model="form.title" type="text" class="ui-input" />
                    </FormField>

                    <FormField label="Fecha" name="date" required :error="errors.date">
                        <input id="date" v-model="form.date" type="date" class="ui-input" />
                    </FormField>

                    <FormField label="Horario" name="start_time" required :error="errors.start_time || errors.end_time">
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

                <FormActions
                    :cancel-href="route('sessions.show', session.id)"
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

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        FormActions,
    },
    props: {
        session: { type: Object, required: true },
        doctors: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
    },
    data() {
        return {
            processing: false,
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
        fullName(person) {
            if (!person) return 'Sin asignar';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || 'Sin asignar';
        },
        submit() {
            this.processing = true;
            router.put(route('sessions.update', this.session.id), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
