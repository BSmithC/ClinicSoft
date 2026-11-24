<template>
    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 p-5 dark:border-slate-700">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Datos de la factura</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <FormField label="Paciente" name="form.patient_id" required :error="errors['form.patient_id']">
                    <select id="bill-patient" v-model="form.patient_id" class="ui-input">
                        <option disabled value="">Seleccione un paciente</option>
                        <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                            {{ patient.name }} {{ patient.last_name }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Médico" name="form.users_id" required :error="errors['form.users_id']">
                    <select id="bill-user" v-model="form.users_id" class="ui-input">
                        <option disabled value="">Seleccione un médico</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }} {{ user.last_name }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Medicamento" name="form.drug_id" required :error="errors['form.drug_id']">
                    <select id="bill-drug" v-model="form.drug_id" class="ui-input">
                        <option disabled value="">Seleccione un medicamento</option>
                        <option v-for="drug in drugs" :key="drug.id" :value="drug.id">
                            {{ drug.name }}
                        </option>
                    </select>
                </FormField>

                <FormField label="Tipo de pago" name="form.payment_type" required :error="errors['form.payment_type']">
                    <select id="bill-payment" v-model="form.payment_type" class="ui-input">
                        <option value="cash">Efectivo</option>
                        <option value="card">Tarjeta</option>
                        <option value="transfer">Transferencia</option>
                    </select>
                </FormField>

                <FormField label="Estado" name="form.status" :error="errors['form.status']">
                    <select id="bill-status" v-model="form.status" class="ui-input">
                        <option :value="true">Pagada</option>
                        <option :value="false">Pendiente</option>
                    </select>
                </FormField>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 p-5 dark:border-slate-700">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Detalle de servicios</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">El ITBIS se calcula automáticamente sobre el subtotal.</p>
                </div>
                <button type="button" class="ui-btn-secondary px-3 py-1.5 text-xs" @click="addRow">
                    <UiIcon name="plus" size="h-3.5 w-3.5" />
                    Agregar servicio
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class="ui-th">Servicio</th>
                            <th class="ui-th w-28">Horas</th>
                            <th class="ui-th w-32">Precio</th>
                            <th class="ui-th w-16 text-right">Quitar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="(detail, index) in details" :key="index">
                            <td class="ui-td align-top">
                                <input
                                    v-model="detail.service"
                                    type="text"
                                    class="ui-input"
                                    :aria-label="`Servicio ${index + 1}`"
                                    placeholder="Descripción del servicio"
                                />
                                <p v-if="errors[`details.${index}.service`]" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ errors[`details.${index}.service`] }}
                                </p>
                            </td>
                            <td class="ui-td align-top">
                                <input v-model.number="detail.hour" type="number" min="0" step="0.5" class="ui-input" :aria-label="`Horas ${index + 1}`" />
                                <p v-if="errors[`details.${index}.hour`]" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ errors[`details.${index}.hour`] }}
                                </p>
                            </td>
                            <td class="ui-td align-top">
                                <input v-model.number="detail.price" type="number" min="0" step="0.01" class="ui-input" :aria-label="`Precio ${index + 1}`" />
                                <p v-if="errors[`details.${index}.price`]" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                                    {{ errors[`details.${index}.price`] }}
                                </p>
                            </td>
                            <td class="ui-td text-right align-top">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                                    :aria-label="`Quitar servicio ${index + 1}`"
                                    :disabled="details.length === 1"
                                    @click="removeRow(index)"
                                >
                                    <UiIcon name="trash" size="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!details.length">
                            <td colspan="4" class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                Agregue al menos un servicio.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="errors.details" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ errors.details }}</p>
        </section>

        <section class="rounded-lg bg-slate-50 p-5 dark:bg-slate-800/50">
            <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Resumen</h2>
            <dl class="ml-auto max-w-sm space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Subtotal</dt>
                    <dd class="font-semibold text-slate-800 dark:text-white">{{ money(subtotal) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">ITBIS (18%)</dt>
                    <dd class="font-semibold text-slate-800 dark:text-white">{{ money(itbis) }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-slate-200 pt-2 dark:border-slate-700">
                    <dt class="font-bold text-slate-800 dark:text-white">Total</dt>
                    <dd class="text-lg font-bold text-brand-600 dark:text-brand-400">{{ money(total) }}</dd>
                </div>
            </dl>
        </section>
    </div>
</template>

<script>
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'BillFormFields',
    components: { FormField, UiIcon },
    props: {
        form: { type: Object, required: true },
        details: { type: Array, required: true },
        errors: { type: Object, default: () => ({}) },
        patients: { type: Array, default: () => [] },
        users: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
    },
    data() {
        return {
            itbisRate: 0.18,
        };
    },
    computed: {
        subtotal() {
            return this.details.reduce((sum, item) => {
                const rowTotal = Number(item.hour) * Number(item.price);
                return sum + (Number.isFinite(rowTotal) ? rowTotal : 0);
            }, 0);
        },
        itbis() {
            return this.subtotal * this.itbisRate;
        },
        total() {
            return this.subtotal + this.itbis;
        },
    },
    methods: {
        addRow() {
            this.details.push({ service: '', hour: 0, price: 0 });
        },
        removeRow(index) {
            if (this.details.length > 1) this.details.splice(index, 1);
        },
        money(value) {
            return new Intl.NumberFormat('es-DO', {
                style: 'currency',
                currency: 'DOP',
                minimumFractionDigits: 2,
            }).format(value);
        },
    },
};
</script>
