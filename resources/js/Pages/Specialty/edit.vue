<template>
    <Head title="Editar especialidad" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Editar especialidad #{{ specialty.id }}</h1>
        </template>

        <section class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Nombre *</label>
                    <input id="name" v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    <InputError :message="errors.name" class="mt-1" />
                </div>
                <div>
                    <label for="slug" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Slug *</label>
                    <input id="slug" v-model="form.slug" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    <InputError :message="errors.slug" class="mt-1" />
                </div>
                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Descripción</label>
                    <textarea id="description" v-model="form.description" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input v-model="form.active" type="checkbox" class="rounded border-slate-300" />
                    Especialidad activa
                </label>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <Link :href="route('specialties.show', specialty.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancelar</Link>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Guardar cambios</button>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        InputError,
    },
    props: {
        specialty: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            form: {
                name: this.specialty.name ?? '',
                slug: this.specialty.slug ?? '',
                description: this.specialty.description ?? '',
                active: Boolean(this.specialty.active),
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
            router.put(route('specialties.update', this.specialty.id), this.form, { preserveScroll: true });
        },
    },
};
</script>
