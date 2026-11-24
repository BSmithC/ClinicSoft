<template>
    <div class="grid gap-5 sm:grid-cols-2">
        <FormField v-if="showUser" label="Usuario" name="user_id" required>
            <select id="user_id" v-model="form.user_id" class="ui-input">
                <option disabled value="">Seleccione un usuario</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                    {{ user.name }} {{ user.last_name }} · {{ user.email }}
                </option>
            </select>
            <InputError :message="errors.user_id" class="mt-1" />
        </FormField>

        <FormField label="Número de licencia" name="license_number">
            <input id="license_number" v-model="form.license_number" type="text" class="ui-input" />
            <InputError :message="errors.license_number" class="mt-1" />
        </FormField>

        <FormField label="Biografía" name="bio" class="sm:col-span-2">
            <textarea id="bio" v-model="form.bio" rows="4" class="ui-input" />
            <InputError :message="errors.bio" class="mt-1" />
        </FormField>

        <FormField label="Especialidades" name="specialties">
            <div class="grid gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700 sm:grid-cols-2">
                <label
                    v-for="specialty in specialties"
                    :key="specialty.id"
                    class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"
                >
                    <input v-model="form.specialties" type="checkbox" :value="specialty.id" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    {{ specialty.name }}
                </label>
                <p v-if="!specialties.length" class="text-sm text-slate-500 dark:text-slate-400">No hay especialidades activas.</p>
            </div>
            <InputError :message="errors.specialties" class="mt-1" />
        </FormField>

        <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700 dark:text-slate-200">
            <input v-model="form.active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
            Perfil activo
        </label>
    </div>
</template>

<script>
import FormField from '@/Components/FormField.vue';
import InputError from '@/Components/InputError.vue';

export default {
    name: 'DoctorFormFields',
    components: { FormField, InputError },
    props: {
        form: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
        users: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        showUser: { type: Boolean, default: false },
    },
};
</script>
