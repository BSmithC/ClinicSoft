<template>
    <Head title="Editar paciente" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Editar paciente #{{ patients.id }}</h1>
        </template>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form @submit.prevent="submit" class="space-y-6">
                <fieldset class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <legend class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Identificación</legend>
                    <div>
                        <label for="DNI" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">DNI *</label>
                        <input id="DNI" v-model="form.DNI" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.DNI" class="mt-1" />
                    </div>
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Nombre *</label>
                        <input id="name" v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.name" class="mt-1" />
                    </div>
                    <div>
                        <label for="sur_name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Primer apellido *</label>
                        <input id="sur_name" v-model="form.sur_name" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.sur_name" class="mt-1" />
                    </div>
                    <div>
                        <label for="last_name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Segundo apellido *</label>
                        <input id="last_name" v-model="form.last_name" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.last_name" class="mt-1" />
                    </div>
                    <div>
                        <label for="second_sur_name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Apellido adicional</label>
                        <input id="second_sur_name" v-model="form.second_sur_name" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <div>
                        <label for="date_of_birth" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Fecha de nacimiento *</label>
                        <input id="date_of_birth" v-model="form.date_of_birth" type="date" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.date_of_birth" class="mt-1" />
                    </div>
                    <div>
                        <label for="place_of_birth" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Lugar de nacimiento *</label>
                        <input id="place_of_birth" v-model="form.place_of_birth" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.place_of_birth" class="mt-1" />
                    </div>
                </fieldset>

                <fieldset class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <legend class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Contacto y demografía</legend>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Correo *</label>
                        <input id="email" v-model="form.email" type="email" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <InputError :message="errors.email" class="mt-1" />
                    </div>
                    <div>
                        <label for="phone_number" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Teléfono</label>
                        <input id="phone_number" v-model="form.phone_number" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <div>
                        <label for="address" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Dirección</label>
                        <input id="address" v-model="form.address" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <div>
                        <label for="nacionality" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Nacionalidad</label>
                        <input id="nacionality" v-model="form.nacionality" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <div>
                        <label for="gender" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Género *</label>
                        <select id="gender" v-model="form.gender" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option v-for="option in genderOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                        <InputError :message="errors.gender" class="mt-1" />
                    </div>
                    <div>
                        <label for="sex" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Sexo *</label>
                        <select id="sex" v-model="form.sex" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option v-for="option in sexOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                        <InputError :message="errors.sex" class="mt-1" />
                    </div>
                    <div>
                        <label for="marital_status" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Estado civil *</label>
                        <select id="marital_status" v-model="form.marital_status" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option v-for="option in maritalStatusOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                        <InputError :message="errors.marital_status" class="mt-1" />
                    </div>
                    <div>
                        <label for="ocupation" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Ocupación</label>
                        <input id="ocupation" v-model="form.ocupation" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                </fieldset>

                <fieldset class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <legend class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Información clínica</legend>
                    <div>
                        <label for="blood_type" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Tipo de sangre</label>
                        <select id="blood_type" v-model="form.blood_type" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option value="">Sin especificar</option>
                            <option v-for="option in bloodTypeOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="insurance" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Seguro</label>
                        <input id="insurance" v-model="form.insurance" type="text" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                    </div>
                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700 dark:text-slate-200">
                        <input v-model="form.status" type="checkbox" class="rounded border-slate-300" />
                        Paciente activo
                    </label>
                </fieldset>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <Link :href="route('patients.show', patients.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Cancelar</Link>
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
        patients: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            genderOptions: ['Masculino', 'Femenino', 'No Binario', 'Otro'],
            sexOptions: ['Masculino', 'Femenino'],
            maritalStatusOptions: ['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Unión libre'],
            bloodTypeOptions: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            form: {
                DNI: this.patients.DNI ?? '',
                name: this.patients.name ?? '',
                sur_name: this.patients.sur_name ?? '',
                last_name: this.patients.last_name ?? '',
                second_sur_name: this.patients.second_sur_name ?? '',
                date_of_birth: String(this.patients.date_of_birth ?? '').slice(0, 10),
                phone_number: this.patients.phone_number ?? '',
                nacionality: this.patients.nacionality ?? '',
                address: this.patients.address ?? '',
                gender: this.patients.gender ?? '',
                sex: this.patients.sex ?? '',
                marital_status: this.patients.marital_status ?? '',
                ocupation: this.patients.ocupation ?? '',
                place_of_birth: this.patients.place_of_birth ?? '',
                blood_type: this.patients.blood_type ?? '',
                insurance: this.patients.insurance ?? '',
                status: Boolean(this.patients.status),
                email: this.patients.email ?? '',
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
            router.put(route('patients.update', this.patients.id), this.form, {
                preserveScroll: true,
            });
        },
    },
};
</script>
