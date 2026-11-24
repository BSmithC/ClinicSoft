<template>
    <Head title="Nueva historia médica" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Nueva historia médica"
                :description="`Registro clínico para ${patientLabel}.`"
            />
        </template>

        <section class="ui-card max-w-5xl p-6">
            <form @submit.prevent="submit">
                <MedicalHistoryFormFields
                    :form="form"
                    :errors="errors"
                    :doctors="doctors"
                    :drugs="drugs"
                    :patient-id="patientId"
                    :patient-label="patientLabel"
                />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('patients.index')"
                        label="Guardar historia médica"
                        :processing="processing"
                    />
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import MedicalHistoryFormFields from '@/Components/MedicalHistoryFormFields.vue';
import FormActions from '@/Components/FormActions.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        MedicalHistoryFormFields,
        FormActions,
    },
    props: {
        patients: { type: [Object, String, Number], default: null },
        doctors: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
    },
    data() {
        return {
            processing: false,
            form: {
                diagnosis: '',
                reason: '',
                symptoms: '',
                diagnosis_description: '',
                description: '',
                blood_pressure: '',
                temperature: '',
                weight: '',
                heart_rate: '',
                observation: '',
                patient_id: this.patients && typeof this.patients === 'object'
                    ? (this.patients.id ?? '')
                    : (this.patients ?? ''),
                doctor_id: '',
                drug_id: '',
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
        patientObject() {
            return this.patients && typeof this.patients === 'object' ? this.patients : null;
        },
        patientId() {
            if (this.patientObject) return this.patientObject.id ?? '';
            return this.patients ?? '';
        },
        patientLabel() {
            if (!this.patientObject) {
                return this.patientId ? `Paciente #${this.patientId}` : 'Paciente no seleccionado';
            }
            const name = `${this.patientObject.name ?? ''} ${this.patientObject.last_name ?? ''}`.trim();
            return name || `Paciente #${this.patientObject.id ?? ''}`;
        },
    },
    methods: {
        submit() {
            this.form.patient_id = this.patientId;
            this.processing = true;
            router.post(route('medicalhistories.store'), { ...this.form }, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
