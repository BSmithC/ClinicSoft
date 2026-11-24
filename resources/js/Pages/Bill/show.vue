<template>
    <Head :title="`Factura #${bills.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Factura #${bills.id}`"
                :description="`Registrada el ${formatDate(bills.created_at)}`"
            >
                <template #actions>
                    <Link :href="route('bills.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver
                    </Link>
                    <Link :href="route('bills.edit', bills.id)" class="ui-btn-primary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="ui-card p-6">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Información de la factura</h2>
                    <dl class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                        <div v-for="field in infoFields" :key="field.label" class="border-b border-slate-100 pb-4 last:border-0 dark:border-slate-800">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ field.label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-white">{{ field.value }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="ui-card overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Servicios</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <th class="ui-th">Servicio</th>
                                    <th class="ui-th">Hora</th>
                                    <th class="ui-th text-right">Precio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="detail in bills.bill_details ?? []" :key="detail.id">
                                    <td class="ui-td">{{ detail.service }}</td>
                                    <td class="ui-td">{{ detail.hour }}</td>
                                    <td class="ui-td text-right">{{ money(detail.price) }}</td>
                                </tr>
                                <tr v-if="!(bills.bill_details ?? []).length">
                                    <td colspan="3" class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                        No hay servicios registrados en esta factura.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="ui-card h-fit p-6">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Resumen</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between gap-4 text-sm">
                        <dt class="text-slate-500 dark:text-slate-400">Subtotal</dt>
                        <dd class="font-semibold text-slate-800 dark:text-white">{{ money(bills.sub_total) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 text-sm">
                        <dt class="text-slate-500 dark:text-slate-400">ITBIS</dt>
                        <dd class="font-semibold text-slate-800 dark:text-white">{{ money(bills.itbis) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                        <dt class="font-bold text-slate-800 dark:text-white">Total</dt>
                        <dd class="text-lg font-bold text-brand-600 dark:text-brand-400">{{ money(bills.total) }}</dd>
                    </div>
                </dl>
                <div class="mt-5 rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800/60">
                    <span class="text-slate-500 dark:text-slate-400">Estado:</span>
                    <span class="ms-1 font-semibold" :class="bills.status ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                        {{ bills.status ? 'Pagada' : 'Pendiente' }}
                    </span>
                </div>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        UiIcon,
    },
    props: {
        bills: { type: Object, required: true },
    },
    computed: {
        infoFields() {
            const drug = Array.isArray(this.bills.drugs) ? this.bills.drugs[0] : this.bills.drugs;

            return [
                { label: 'Paciente', value: this.fullName(this.bills.patient) },
                { label: 'Médico', value: `Dr. ${this.fullName(this.bills.user)}` },
                { label: 'Medicamento', value: drug?.name ?? '—' },
                { label: 'Tipo de pago', value: this.paymentLabel(this.bills.payment_type) },
                { label: 'Registro', value: this.formatDate(this.bills.created_at) },
            ];
        },
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        paymentLabel(value) {
            const labels = { cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transferencia' };
            return labels[value] ?? value ?? '—';
        },
        money(value) {
            if (value === null || value === undefined) return '—';
            return new Intl.NumberFormat('es-DO', {
                style: 'currency',
                currency: 'DOP',
                minimumFractionDigits: 2,
            }).format(Number(value));
        },
        formatDate(value) {
            if (!value) return '—';
            return String(value).slice(0, 10);
        },
    },
};
</script>
