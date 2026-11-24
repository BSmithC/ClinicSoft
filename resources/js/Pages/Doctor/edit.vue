<template>
    <Head title="Editar médico" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Editar perfil médico #{{ doctor.id }}</h1>
        </template>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="license_number" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Número de licencia</label>
                        <input id="license_number" v-model="form.license_number" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.license_number" class="mt-1" />
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">Especialidades</p>
                        <label v-for="specialty in specialties" :key="specialty.id" class="mb-1 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input v-model="form.specialties" type="checkbox" :value="specialty.id" class="rounded border-slate-300" />
                            {{ specialty.name }}
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="bio" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Biografía</label>
                        <textarea id="bio" v-model="form.bio" rows="4" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input v-model="form.active" type="checkbox" class="rounded border-slate-300" />
                        Perfil activo
                    </label>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <Link :href="route('doctors.show', doctor.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancelar</Link>
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
        doctor: {
            type: Object,
            required: true,
        },
        specialties: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            form: {
                license_number: this.doctor.license_number ?? '',
                bio: this.doctor.bio ?? '',
                active: Boolean(this.doctor.active),
                specialties: (this.doctor.specialties ?? []).map(item => item.id),
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
            router.put(route('doctors.update', this.doctor.id), this.form, { preserveScroll: true });
        },
    },
};
</script>
