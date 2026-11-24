<template>
    <Head title="Editar médico" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Editar perfil médico #${doctor.id}`"
                description="Actualiza la información clínica y las especialidades asignadas."
            />
        </template>

        <section class="ui-card p-6">
            <form @submit.prevent="submit">
                <DoctorFormFields
                    :form="form"
                    :errors="errors"
                    :specialties="specialties"
                />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('doctors.show', doctor.id)"
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
        doctor: { type: Object, required: true },
        specialties: { type: Array, default: () => [] },
    },
    data() {
        return {
            processing: false,
            form: {
                license_number: this.doctor.license_number ?? '',
                bio: this.doctor.bio ?? '',
                active: Boolean(this.doctor.active),
                specialties: (this.doctor.specialties ?? []).map((item) => item.id),
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
            router.put(route('doctors.update', this.doctor.id), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
