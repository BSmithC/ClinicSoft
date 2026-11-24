<template>
    <Head title="Nuevo médico" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Nuevo perfil médico"
                description="Solo usuarios con rol de médico y sin perfil clínico."
            />
        </template>

        <section class="ui-card p-6">
            <form @submit.prevent="submit">
                <DoctorFormFields
                    :form="form"
                    :errors="errors"
                    :users="users"
                    :specialties="specialties"
                    show-user
                />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('doctors.index')"
                        label="Guardar perfil"
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
import DoctorFormFields from '@/Components/DoctorFormFields.vue';
import FormActions from '@/Components/FormActions.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        DoctorFormFields,
        FormActions,
    },
    props: {
        users: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
    },
    data() {
        return {
            processing: false,
            form: {
                user_id: '',
                license_number: '',
                bio: '',
                active: true,
                specialties: [],
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
            router.post(route('doctors.store'), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
