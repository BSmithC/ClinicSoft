<template>
    <Head title="Nueva especialidad" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Nueva especialidad"
                description="Catálogo usado al programar sesiones y citas."
            />
        </template>

        <section class="ui-card max-w-2xl p-6">
            <form @submit.prevent="submit" class="space-y-5">
                <FormField label="Nombre" name="name" required>
                    <input id="name" v-model="form.name" type="text" class="ui-input" />
                    <InputError :message="errors.name" class="mt-1" />
                </FormField>
                <FormField label="Slug" name="slug" required hint="Solo letras minúsculas, números y guiones.">
                    <input id="slug" v-model="form.slug" type="text" class="ui-input" placeholder="cardiologia" />
                    <InputError :message="errors.slug" class="mt-1" />
                </FormField>
                <FormField label="Descripción" name="description">
                    <textarea id="description" v-model="form.description" rows="3" class="ui-input" />
                    <InputError :message="errors.description" class="mt-1" />
                </FormField>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input v-model="form.active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    Especialidad activa
                </label>

                <FormActions
                    :cancel-href="route('specialties.index')"
                    label="Guardar"
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
import InputError from '@/Components/InputError.vue';
import FormActions from '@/Components/FormActions.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        InputError,
        FormActions,
    },
    data() {
        return {
            processing: false,
            form: {
                name: '',
                slug: '',
                description: '',
                active: true,
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
            router.post(route('specialties.store'), this.form, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
