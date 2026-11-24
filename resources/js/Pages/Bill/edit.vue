<template>
    <Head title="Editar factura" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Editar factura #${bills.id}`"
                description="Actualice los datos y los servicios de la factura. El ITBIS se recalcula automáticamente."
            />
        </template>

        <section class="ui-card p-6">
            <form @submit.prevent="submit">
                <BillFormFields
                    :form="form"
                    :details="details"
                    :errors="errors"
                    :patients="patients"
                    :users="users"
                    :drugs="drugs"
                />
                <div class="mt-6">
                    <FormActions
                        :cancel-href="route('bills.show', bills.id)"
                        label="Guardar cambios"
                        :processing="processing"
                    />
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BillFormFields from '@/Components/BillFormFields.vue';
import FormActions from '@/Components/FormActions.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        PageHeader,
        BillFormFields,
        FormActions,
    },
    props: {
        bills: { type: Object, required: true },
        users: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
    },
    data() {
        return {
            processing: false,
            form: {
                drug_id: this.drugId(this.bills),
                users_id: this.bills.users_id ?? '',
                patient_id: this.bills.patient_id ?? '',
                itbis: String(this.bills.itbis ?? '0.00'),
                sub_total: String(this.bills.sub_total ?? '0.00'),
                total: String(this.bills.total ?? '0.00'),
                payment_type: this.bills.payment_type ?? 'cash',
                status: Boolean(this.bills.status),
            },
            details: (this.bills.bill_details ?? []).map((detail) => ({
                service: detail.service ?? '',
                hour: detail.hour ?? 0,
                price: detail.price ?? 0,
            })),
        };
    },
    computed: {
        errors() {
            return this.$page.props.errors ?? {};
        },
        subtotal() {
            return this.details.reduce((sum, item) => {
                const rowTotal = Number(item.hour) * Number(item.price);
                return sum + (Number.isFinite(rowTotal) ? rowTotal : 0);
            }, 0);
        },
        itbis() {
            return this.subtotal * 0.18;
        },
        total() {
            return this.subtotal + this.itbis;
        },
    },
    methods: {
        drugId(bill) {
            const drug = Array.isArray(bill.drugs) ? bill.drugs[0] : bill.drugs;
            return drug?.id ?? bill.drug_id ?? '';
        },
        submit() {
            this.form.sub_total = this.subtotal.toFixed(2);
            this.form.itbis = this.itbis.toFixed(2);
            this.form.total = this.total.toFixed(2);

            this.processing = true;
            router.put(route('bills.update', this.bills.id), {
                form: { ...this.form },
                details: this.details.map(({ service, hour, price }) => ({ service, hour, price })),
            }, {
                preserveScroll: true,
                onFinish: () => { this.processing = false; },
            });
        },
    },
};
</script>
