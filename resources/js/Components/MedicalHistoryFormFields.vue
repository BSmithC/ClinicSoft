<template>
    <div class="space-y-6">
        <input v-if="patientId" type="hidden" name="patient_id" :value="patientId" />

        <section class="rounded-lg border border-slate-200 p-5 dark:border-slate-700">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Consulta</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60 sm:col-span-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Paciente</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-white">{{ patientLabel }}</p>
                </div>

                <FormField label="Médico" name="doctor_id" required :error="errors.doctor_id">
                    <select id="history-doctor" v-model="form.doctor_id" class="ui-input">
                        <option disabled value="">Seleccione un médico</option>
                        <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                            {{ doctor.name }} {{ doctor.last_name }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Medicamento" name="drug_id" required :error="errors.drug_id">
                    <select id="history-drug" v-model="form.drug_id" class="ui-input">
                        <option disabled value="">Seleccione un medicamento</option>
                        <option v-for="drug in drugs" :key="drug.id" :value="drug.id">{{ drug.name }}</option>
                    </select>
                </FormField>

                <FormField label="Motivo principal" name="reason" required :error="errors.reason" class="sm:col-span-2">
                    <input id="history-reason" v-model="form.reason" type="text" class="ui-input" placeholder="Motivo de la consulta" />
                </FormField>

                <FormField label="Síntomas" name="symptoms" required :error="errors.symptoms" class="sm:col-span-2">
                    <textarea id="history-symptoms" v-model="form.symptoms" rows="3" class="ui-input" />
                </FormField>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 p-5 dark:border-slate-700">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Examen físico</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <FormField label="Presión arterial" name="blood_pressure" required :error="errors.blood_pressure">
                    <input id="history-pressure" v-model="form.blood_pressure" type="text" class="ui-input" placeholder="120/80" />
                </FormField>
                <FormField label="Temperatura" name="temperature" required :error="errors.temperature">
                    <input id="history-temperature" v-model="form.temperature" type="text" class="ui-input" placeholder="36.5" />
                </FormField>
                <FormField label="Peso" name="weight" required :error="errors.weight">
                    <input id="history-weight" v-model="form.weight" type="text" class="ui-input" placeholder="68" />
                </FormField>
                <FormField label="Frecuencia cardíaca" name="heart_rate" required :error="errors.heart_rate">
                    <input id="history-heart-rate" v-model="form.heart_rate" type="text" class="ui-input" placeholder="72" />
                </FormField>
            </div>
            <div class="mt-4">
                <FormField label="Observaciones" name="observation" required :error="errors.observation">
                    <textarea id="history-observation" v-model="form.observation" rows="2" class="ui-input" />
                </FormField>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 p-5 dark:border-slate-700">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Diagnóstico y tratamiento</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Diagnóstico" name="diagnosis" required :error="errors.diagnosis" class="sm:col-span-2">
                    <input id="history-diagnosis" v-model="form.diagnosis" type="text" class="ui-input" />
                </FormField>
                <FormField label="Descripción del diagnóstico" name="diagnosis_description" required :error="errors.diagnosis_description" class="sm:col-span-2">
                    <textarea id="history-diagnosis-description" v-model="form.diagnosis_description" rows="2" class="ui-input" />
                </FormField>
                <FormField label="Indicaciones" name="description" required :error="errors.description" class="sm:col-span-2">
                    <textarea id="history-description" v-model="form.description" rows="3" class="ui-input" />
                </FormField>
            </div>
        </section>
    </div>
</template>

<script>
import FormField from '@/Components/FormField.vue';

export default {
    name: 'MedicalHistoryFormFields',
    components: { FormField },
    props: {
        form: { type: Object, required: true },
        errors: { type: Object, default: () => ({}) },
        doctors: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
        patientLabel: { type: String, default: '—' },
        patientId: { type: [String, Number], default: '' },
    },
};
</script>
