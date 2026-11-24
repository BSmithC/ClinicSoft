<template>
    <Head title="Mi perfil" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Configuración"
                title="Mi perfil"
                description="Administre sus datos personales, su contraseña y el estado de su cuenta."
            />
        </template>

        <div class="mx-auto max-w-3xl space-y-6">
            <section class="ui-card p-6">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </section>

            <section class="ui-card p-6">
                <UpdatePasswordForm />
            </section>

            <section class="ui-card p-6">
                <DeleteUserForm />
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

export default {
    name: 'ProfileEdit',
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        DeleteUserForm,
        UpdatePasswordForm,
        UpdateProfileInformationForm,
    },
    props: {
        mustVerifyEmail: { type: Boolean, default: false },
        status: { type: String, default: '' },
    },
};
</script>
