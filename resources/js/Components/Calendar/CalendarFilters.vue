<template>
    <section class="ui-card p-4" aria-label="Filtros de la agenda">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <FormField v-if="isAdmin" label="Médico" name="calendar-filter-doctor">
                <select id="calendar-filter-doctor" class="ui-input" :value="filters.doctor_id || ''" @change="update('doctor_id', $event.target.value)">
                    <option value="">Todos los médicos</option>
                    <option v-for="doctor in doctors" :key="doctor.id" :value="String(doctor.user_id || doctor.id)">
                        Dr. {{ doctor.user?.name ?? '' }} {{ doctor.user?.last_name ?? '' }}
                    </option>
                </select>
            </FormField>

            <FormField label="Especialidad" name="calendar-filter-specialty">
                <select id="calendar-filter-specialty" class="ui-input" :value="filters.specialty_id || ''" @change="update('specialty_id', $event.target.value)">
                    <option value="">Todas las especialidades</option>
                    <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">
                        {{ specialty.name }}
                    </option>
                </select>
            </FormField>

            <FormField label="Estado" name="calendar-filter-status">
                <select id="calendar-filter-status" class="ui-input" :value="filters.status || ''" @change="update('status', $event.target.value)">
                    <option value="">Todos los estados</option>
                    <option v-for="status in statuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </FormField>

            <FormField label="Paciente" name="calendar-filter-patient">
                <div class="relative">
                    <UiIcon name="search" size="h-4 w-4" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        id="calendar-filter-patient"
                        type="search"
                        class="ui-input pl-9"
                        :value="filters.patient || ''"
                        placeholder="Nombre del paciente"
                        @input="update('patient', $event.target.value)"
                    />
                </div>
            </FormField>
        </div>
    </section>
</template>

<script>
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'CalendarFilters',
    components: { FormField, UiIcon },
    props: {
        filters: { type: Object, default: () => ({}) },
        doctors: { type: Array, default: () => [] },
        specialties: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
        isAdmin: { type: Boolean, default: false },
    },
    emits: ['update:filters'],
    methods: {
        update(key, value) {
            this.$emit('update:filters', { ...this.filters, [key]: value });
        },
    },
};
</script>
