<template>
    <div class="space-y-6">
        <section>
            <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Identificación</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <FormField label="DNI" name="DNI" required>
                    <input id="DNI" v-model="form.DNI" type="text" class="ui-input" />
                    <InputError :message="errors.DNI" class="mt-1" />
                </FormField>
                <FormField label="Nombre" name="name" required>
                    <input id="name" v-model="form.name" type="text" class="ui-input" />
                    <InputError :message="errors.name" class="mt-1" />
                </FormField>
                <FormField label="Primer apellido" name="sur_name" required>
                    <input id="sur_name" v-model="form.sur_name" type="text" class="ui-input" />
                    <InputError :message="errors.sur_name" class="mt-1" />
                </FormField>
                <FormField label="Segundo apellido" name="last_name" required>
                    <input id="last_name" v-model="form.last_name" type="text" class="ui-input" />
                    <InputError :message="errors.last_name" class="mt-1" />
                </FormField>
                <FormField label="Apellido adicional" name="second_sur_name">
                    <input id="second_sur_name" v-model="form.second_sur_name" type="text" class="ui-input" />
                    <InputError :message="errors.second_sur_name" class="mt-1" />
                </FormField>
                <FormField label="Fecha de nacimiento" name="date_of_birth" required>
                    <input id="date_of_birth" v-model="form.date_of_birth" type="date" class="ui-input" />
                    <InputError :message="errors.date_of_birth" class="mt-1" />
                </FormField>
                <FormField label="Lugar de nacimiento" name="place_of_birth" required>
                    <input id="place_of_birth" v-model="form.place_of_birth" type="text" class="ui-input" />
                    <InputError :message="errors.place_of_birth" class="mt-1" />
                </FormField>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Contacto y demografía</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <FormField label="Correo" name="email" required>
                    <input id="email" v-model="form.email" type="email" class="ui-input" />
                    <InputError :message="errors.email" class="mt-1" />
                </FormField>
                <FormField label="Teléfono" name="phone_number">
                    <input id="phone_number" v-model="form.phone_number" type="text" class="ui-input" />
                    <InputError :message="errors.phone_number" class="mt-1" />
                </FormField>
                <FormField label="Dirección" name="address">
                    <input id="address" v-model="form.address" type="text" class="ui-input" />
                </FormField>
                <FormField label="Nacionalidad" name="nacionality">
                    <input id="nacionality" v-model="form.nacionality" type="text" class="ui-input" />
                </FormField>
                <FormField label="Género" name="gender" required>
                    <select id="gender" v-model="form.gender" class="ui-input">
                        <option disabled value="">Seleccione</option>
                        <option v-for="option in genderOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <InputError :message="errors.gender" class="mt-1" />
                </FormField>
                <FormField label="Sexo" name="sex" required>
                    <select id="sex" v-model="form.sex" class="ui-input">
                        <option disabled value="">Seleccione</option>
                        <option v-for="option in sexOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <InputError :message="errors.sex" class="mt-1" />
                </FormField>
                <FormField label="Estado civil" name="marital_status" required>
                    <select id="marital_status" v-model="form.marital_status" class="ui-input">
                        <option disabled value="">Seleccione</option>
                        <option v-for="option in maritalStatusOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <InputError :message="errors.marital_status" class="mt-1" />
                </FormField>
                <FormField label="Ocupación" name="ocupation">
                    <input id="ocupation" v-model="form.ocupation" type="text" class="ui-input" />
                </FormField>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Información clínica</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <FormField label="Tipo de sangre" name="blood_type">
                    <select id="blood_type" v-model="form.blood_type" class="ui-input">
                        <option value="">Sin especificar</option>
                        <option v-for="option in bloodTypeOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <InputError :message="errors.blood_type" class="mt-1" />
                </FormField>
                <FormField label="Seguro" name="insurance">
                    <input id="insurance" v-model="form.insurance" type="text" class="ui-input" />
                    <InputError :message="errors.insurance" class="mt-1" />
                </FormField>
                <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700 dark:text-slate-200">
                    <input v-model="form.status" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    Paciente activo
                </label>
            </div>
        </section>
    </div>
</template>

<script>
import FormField from '@/Components/FormField.vue';
import InputError from '@/Components/InputError.vue';

export default {
    name: 'PatientFormFields',
    components: { FormField, InputError },
    props: {
        form: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            genderOptions: ['Masculino', 'Femenino', 'No Binario', 'Otro'],
            sexOptions: ['Masculino', 'Femenino'],
            maritalStatusOptions: ['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Unión libre'],
            bloodTypeOptions: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        };
    },
};
</script>
