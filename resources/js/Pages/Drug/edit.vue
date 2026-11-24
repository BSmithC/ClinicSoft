<template>
    <Head title="Editar medicamento" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Catálogo clínico"
                :title="`Editar medicamento #${drugs.id}`"
                :description="`Actualiza la información de ${drugs.name ?? 'este medicamento'}.`"
            />
        </template>

        <section class="ui-card max-w-4xl p-6">
            <form @submit.prevent="submit">
                <DrugFormFields :form="form" :errors="errors" />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('drugs.show', drugs.id)"
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
    props: {
        drugs: { type: Object, required: true },
    },
    data() {
        return {
            processing: false,
            form: {
                name: this.drugs.name ?? '',
                description: this.drugs.description ?? '',
                route: this.drugs.route ?? '',
                frequency: this.drugs.frequency ?? '',
                duration: this.drugs.duration ?? '',
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
            router.put(route('drugs.update', this.drugs.id), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
