<template>
    <Head title="Editar paciente" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Editar paciente #${patients.id}`"
                :description="`Ficha de ${patients.name ?? ''} ${patients.last_name ?? ''}`.trim()"
            />
        </template>

        <section class="ui-card p-6">
            <form @submit.prevent="submit">
                <PatientFormFields :form="form" :errors="errors" />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('patients.show', patients.id)"
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
import PatientFormFields from '@/Components/PatientFormFields.vue';
import FormActions from '@/Components/FormActions.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        PatientFormFields,
        FormActions,
    },
    props: {
        patients: { type: Object, required: true },
    },
    data() {
        return {
            processing: false,
            form: {
                DNI: this.patients.DNI ?? '',
                name: this.patients.name ?? '',
                sur_name: this.patients.sur_name ?? '',
                last_name: this.patients.last_name ?? '',
                second_sur_name: this.patients.second_sur_name ?? '',
                date_of_birth: String(this.patients.date_of_birth ?? '').slice(0, 10),
                phone_number: this.patients.phone_number ?? '',
                nacionality: this.patients.nacionality ?? '',
                address: this.patients.address ?? '',
                gender: this.patients.gender ?? '',
                sex: this.patients.sex ?? '',
                marital_status: this.patients.marital_status ?? '',
                ocupation: this.patients.ocupation ?? '',
                place_of_birth: this.patients.place_of_birth ?? '',
                blood_type: this.patients.blood_type ?? '',
                insurance: this.patients.insurance ?? '',
                status: Boolean(this.patients.status),
                email: this.patients.email ?? '',
            },
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
    },
    methods: {
        submit() {
            this.processing = true;
            router.put(route('patients.update', this.patients.id), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
