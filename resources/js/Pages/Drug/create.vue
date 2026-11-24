<template>
    <Head title="Nuevo medicamento" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Catálogo clínico"
                title="Nuevo medicamento"
                description="Los datos se validan en el servidor antes de guardarse."
            />
        </template>

        <section class="ui-card max-w-4xl p-6">
            <form @submit.prevent="submit">
                <DrugFormFields :form="form" :errors="errors" />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('drugs.index')"
                        label="Guardar medicamento"
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
import DrugFormFields from '@/Components/DrugFormFields.vue';
import FormActions from '@/Components/FormActions.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        DrugFormFields,
        FormActions,
    },
    data() {
        return {
            processing: false,
            form: {
                name: '',
                description: '',
                route: '',
                frequency: '',
                duration: '',
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
            router.post(route('drugs.store'), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
