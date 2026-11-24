<template>
    <Head title="Nueva factura" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Nueva factura"
                description="Los importes se calculan a partir de los servicios registrados y se validan en el servidor."
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
                        :cancel-href="route('bills.index')"
                        label="Guardar factura"
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
        patients: { type: Array, default: () => [] },
        users: { type: Array, default: () => [] },
        drugs: { type: Array, default: () => [] },
    },
    data() {
        return {
            processing: false,
            form: {
                drug_id: '',
                users_id: '',
                patient_id: '',
                itbis: '',
                sub_total: '',
                total: '',
                payment_type: 'cash',
                status: true,
            },
            details: [{ service: '', hour: 0, price: 0 }],
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
        submit() {
            this.form.sub_total = this.subtotal.toFixed(2);
            this.form.itbis = this.itbis.toFixed(2);
            this.form.total = this.total.toFixed(2);

            this.processing = true;
            router.post(route('bills.store'), {
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
