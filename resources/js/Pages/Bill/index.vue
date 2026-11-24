<template>
    <Head title="Facturación" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                title="Facturación"
                description="Facturas registradas con sus servicios, medicamentos y estado de pago."
            >
                <template #actions>
                    <Link :href="route('bills.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nueva factura
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-5">
            <section class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                <SearchInput
                    v-model="form.search"
                    placeholder="Buscar factura"
                    input-id="bills-search"
                    class="sm:flex-1"
                    width="w-full"
                    @search="submit"
                />
                <FilterDropdown
                    v-model="form.lastDays"
                    name="lastDays"
                    label="Periodo"
                    placeholder="Todos los periodos"
                    :options="periodOptions"
                    width="w-full sm:w-52"
                    @update:model-value="submit"
                />
                <button type="button" class="ui-btn-primary w-full sm:w-auto" :disabled="loading" @click="submit">
                    <UiIcon name="search" size="h-4 w-4" />
                    Buscar
                </button>
            </section>

            <DataTable
                :columns="columns"
                :rows="bills.data"
                :loading="loading"
                empty-title="No hay facturas"
                empty-description="No hay facturas que coincidan con los filtros seleccionados."
            >
                <template #cell-id="{ row }">
                    <Link :href="route('bills.show', row.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                        #{{ row.id }}
                    </Link>
                </template>

                <template #cell-patient="{ row }">
                    <span class="block font-medium text-slate-800 dark:text-white">
                        {{ fullName(row.patient) }}
                    </span>
                </template>

                <template #cell-user="{ row }">
                    {{ fullName(row.user) }}
                </template>

                <template #cell-drug="{ row }">
                    {{ drugName(row) }}
                </template>

                <template #cell-payment_type="{ row }">
                    <span class="capitalize">{{ paymentLabel(row.payment_type) }}</span>
                </template>

                <template #cell-total="{ row }">
                    <span class="font-semibold text-slate-800 dark:text-white">{{ money(row.total) }}</span>
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                        :class="row.status
                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20'
                            : 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20'"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
                        {{ row.status ? 'Pagada' : 'Pendiente' }}
                    </span>
                </template>

                <template #cell-created_at="{ row }">
                    {{ formatDate(row.created_at) }}
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <Link :href="route('bills.show', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Ver detalle">
                            <UiIcon name="eye" size="h-4 w-4" />
                        </Link>
                        <Link :href="route('bills.edit', row.id)" class="ui-btn-ghost px-2 py-1.5" title="Editar">
                            <UiIcon name="pencil" size="h-4 w-4" />
                        </Link>
                    </div>
                </template>

                <template #emptyActions>
                    <Link :href="route('bills.create')" class="ui-btn-primary">Crear factura</Link>
                </template>
            </DataTable>

            <Pagination :pagination="bills" :filters="activeFilters" />
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchInput from '@/Components/SearchInput.vue';
import FilterDropdown from '@/Components/FilterDropdown.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        SearchInput,
        FilterDropdown,
        DataTable,
        Pagination,
        UiIcon,
    },
    props: {
        bills: { type: Object, required: true },
        filters: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            loading: false,
            form: {
                search: this.filters.search ?? '',
                lastDays: this.filters.lastDays ?? '30',
            },
            columns: [
                { key: 'id', label: 'Factura' },
                { key: 'patient', label: 'Paciente' },
                { key: 'user', label: 'Médico' },
                { key: 'drug', label: 'Medicamento' },
                { key: 'payment_type', label: 'Pago' },
                { key: 'total', label: 'Total' },
                { key: 'status', label: 'Estado' },
                { key: 'created_at', label: 'Registro' },
                { key: 'actions', label: 'Acciones', align: 'right' },
            ],
            periodOptions: [
                { value: '7', label: 'Últimos 7 días' },
                { value: '30', label: 'Últimos 30 días' },
                { value: 'month', label: 'Este mes' },
                { value: 'year', label: 'Este año' },
                { value: 'all', label: 'Todos' },
            ],
        };
    },
    computed: {
        activeFilters() {
            return Object.fromEntries(
                Object.entries(this.form).filter(([, value]) => value !== '' && value !== null)
            );
        },
    },
    methods: {
        submit() {
            this.loading = true;
            router.get(route('bills.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => { this.loading = false; },
            });
        },
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        drugName(bill) {
            const drug = Array.isArray(bill.drugs) ? bill.drugs[0] : bill.drugs;
            return drug?.name ?? '—';
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
