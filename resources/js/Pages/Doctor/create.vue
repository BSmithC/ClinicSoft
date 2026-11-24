<template>
    <Head title="Nuevo médico" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Nuevo perfil médico</h1>
            <p class="text-sm text-slate-500">Solo usuarios con rol de médico y sin perfil clínico.</p>
        </template>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="user_id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Usuario *</label>
                        <select id="user_id" v-model="form.user_id" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option disabled value="">Seleccione un usuario</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} {{ user.last_name }} · {{ user.email }}</option>
                        </select>
                        <InputError :message="errors.user_id" class="mt-1" />
                    </div>
                    <div>
                        <label for="license_number" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Número de licencia</label>
                        <input id="license_number" v-model="form.license_number" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.license_number" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="bio" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Biografía</label>
                        <textarea id="bio" v-model="form.bio" rows="4" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">Especialidades</p>
                        <label v-for="specialty in specialties" :key="specialty.id" class="mb-1 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input v-model="form.specialties" type="checkbox" :value="specialty.id" class="rounded border-slate-300" />
                            {{ specialty.name }}
                        </label>
                        <p v-if="!specialties.length" class="text-sm text-slate-500">No hay especialidades activas.</p>
                        <InputError :message="errors.specialties" class="mt-1" />
                    </div>
                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700 dark:text-slate-200">
                        <input v-model="form.active" type="checkbox" class="rounded border-slate-300" />
                        Perfil activo
                    </label>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <Link :href="route('doctors.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancelar</Link>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Guardar perfil</button>
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
        users: {
            type: Array,
            default: () => [],
        },
        specialties: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
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
            router.post(route('doctors.store'), this.form, { preserveScroll: true });
        },
    },
};
</script>
