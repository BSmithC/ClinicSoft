<template>
    <Head title="Editar historia médica" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Editar historia #${medicalhistories.id}`"
                :description="`Registro clínico de ${patientLabel}.`"
            />
        </template>

        <section class="ui-card max-w-5xl p-6">
            <form @submit.prevent="submit">
                <MedicalHistoryFormFields
                    :form="form"
                    :errors="errors"
                    :doctors="doctors"
                    :drugs="drugs"
                    :patient-id="medicalhistories.patient_id"
                    :patient-label="patientLabel"
                />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('medicalhistories.show', medicalhistories.id)"
                        label="Guardar cambios"
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
        medicalhistories: { type: Object, required: true },
        doctors: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
    },
    data() {
        return {
            processing: false,
            form: {
                diagnosis: this.medicalhistories.diagnosis ?? '',
                reason: this.medicalhistories.reason ?? '',
                symptoms: this.medicalhistories.symptoms ?? '',
                diagnosis_description: this.medicalhistories.diagnosis_description ?? '',
                description: this.medicalhistories.description ?? '',
                blood_pressure: this.medicalhistories.blood_pressure ?? '',
                temperature: this.medicalhistories.temperature ?? '',
                weight: this.medicalhistories.weight ?? '',
                heart_rate: this.medicalhistories.heart_rate ?? '',
                observation: this.medicalhistories.observation ?? '',
                patient_id: this.medicalhistories.patient_id ?? '',
                doctor_id: this.medicalhistories.doctor_id ?? '',
                drug_id: this.medicalhistories.drug_id ?? '',
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
        patientLabel() {
            const patient = this.medicalhistories.patient;
            if (!patient) return `Paciente #${this.medicalhistories.patient_id ?? ''}`;
            const name = `${patient.name ?? ''} ${patient.last_name ?? ''}`.trim();
            return name || `Paciente #${patient.id ?? ''}`;
        },
    },
    methods: {
        submit() {
            this.processing = true;
            router.put(route('medicalhistories.update', this.medicalhistories.id), { ...this.form }, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
