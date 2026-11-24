<template>
    <Head title="Nuevo paciente" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Nuevo paciente"
                description="Los datos se validan en el servidor antes de guardarse."
            />
        </template>

        <section class="ui-card p-6">
            <form @submit.prevent="submit">
                <PatientFormFields :form="form" :errors="errors" />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('patients.index')"
                        label="Guardar paciente"
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
    data() {
        return {
            processing: false,
            form: {
                DNI: '',
                name: '',
                sur_name: '',
                last_name: '',
                second_sur_name: '',
                date_of_birth: '',
                phone_number: '',
                nacionality: '',
                address: '',
                gender: '',
                sex: '',
                marital_status: '',
                ocupation: '',
                place_of_birth: '',
                blood_type: '',
                insurance: '',
                status: true,
                email: '',
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
            router.post(route('patients.store'), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
