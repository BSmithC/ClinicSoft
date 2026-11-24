<template>
    <div class="grid gap-5 sm:grid-cols-2">
        <FormField label="Nombre" name="name" required :error="errors.name">
            <input id="name" v-model="form.name" type="text" class="ui-input" placeholder="Ej. Amoxicilina" />
        </FormField>

        <FormField label="Vía de administración" name="route" required :error="errors.route">
            <select id="route" v-model="form.route" class="ui-input">
                <option disabled value="">Seleccione una vía</option>
                <option v-for="option in routeOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </FormField>

        <FormField label="Frecuencia" name="frequency" required :error="errors.frequency">
            <input id="frequency" v-model="form.frequency" type="text" class="ui-input" placeholder="Ej. Cada 8 horas" />
        </FormField>

        <FormField label="Duración" name="duration" required :error="errors.duration">
            <input id="duration" v-model="form.duration" type="text" class="ui-input" placeholder="Ej. 7 días" />
        </FormField>

        <FormField label="Descripción" name="description" class="sm:col-span-2" :error="errors.description">
            <textarea id="description" v-model="form.description" rows="4" class="ui-input" placeholder="Indicaciones o instrucciones especiales" />
        </FormField>
    </div>
</template>

<script>
import FormField from '@/Components/FormField.vue';

export default {
    name: 'DrugFormFields',
    components: { FormField },
    props: {
        form: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            routeOptions: [
                { value: 'Oral', label: 'Oral' },
                { value: 'Intravenous', label: 'Intravenosa' },
                { value: 'Intramuscular', label: 'Intramuscular' },
                { value: 'Subcutaneous', label: 'Subcutánea' },
                { value: 'Topical', label: 'Tópica' },
                { value: 'Inhalation', label: 'Inhalación' },
            ],
        };
    },
};
</script>
