<template>
    <Head title="Dashboard - Gestión Clínica" />

    <AuthenticatedLayout>
        <template #header>
            <!-- KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <CardStats
                    statSubtitle="Pacientes"
                    :statTitle="Kpis.PatientsAll"
                    statIconName="fas fa-user-injured"
                    statIconColor="bg-blue-500"
                    :show-trend="false"
                />
                <CardStats
                    statSubtitle="Medicamentos"
                    :statTitle="Kpis.DrugsAll"
                    statIconName="fas fa-pills"
                    statIconColor="bg-teal-500"
                    :show-trend="false"
                />
                <CardStats
                    statSubtitle="Médicos activos"
                    :statTitle="Kpis.ActiveDoctors"
                    statIconName="fas fa-user-md"
                    statIconColor="bg-indigo-500"
                    :show-trend="false"
                />
                <CardStats
                    statSubtitle="Facturación total"
                    :statTitle="totalBilling"
                    statIconName="fas fa-file-invoice-dollar"
                    statIconColor="bg-emerald-500"
                    :show-trend="false"
                />
                <CardStats
                    statSubtitle="Citas totales"
                    :statTitle="Kpis.AppointmentsAll"
                    statIconName="fas fa-calendar-check"
                    statIconColor="bg-purple-500"
                    :show-trend="false"
                />
                <CardStats
                    statSubtitle="Citas de hoy"
                    :statTitle="Kpis.AppointmentsToday"
                    statIconName="fas fa-calendar-day"
                    statIconColor="bg-sky-500"
                    :show-trend="false"
                />
                <CardStats
                    statSubtitle="Pendientes"
                    :statTitle="Kpis.PendingAppointments"
                    statIconName="fas fa-hourglass-half"
                    statIconColor="bg-amber-500"
                    :show-trend="false"
                />
                <CardStats
                    statSubtitle="Completadas"
                    :statTitle="Kpis.CompletedAppointments"
                    statIconName="fas fa-circle-check"
                    statIconColor="bg-green-500"
                    :show-trend="false"
                />
            </div>

            <!-- GRÁFICA -->
            <apexchart
                type="bar"
                height="350"
                :options="chartOptions"
                :series="series"
            />
        </template>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import CardStats from '@/Components/CardStats.vue'
import ApexChart from 'vue3-apexcharts'

export default {
    components: {
        AuthenticatedLayout,
        Head,
        CardStats,
        apexchart: ApexChart,
    },

    props: {
        Kpis: {
            type: Object,
            required: true,
        },
    },

    computed: {
        totalBilling() {
            return new Intl.NumberFormat('es-ES', {
                maximumFractionDigits: 2,
            }).format(Number(this.Kpis.TotalBilling || 0));
        },

        series() {
            return [
                {
                    name: 'Totales',
                    data: [
                        this.Kpis.PatientsAll,
                        this.Kpis.DrugsAll,
                        this.Kpis.AppointmentsAll,
                    ],
                },
            ]
        },

        chartOptions() {
            return {
                chart: {
                    toolbar: { show: false },
                },
                plotOptions: {
                    bar: {
                        distributed: true,
                        columnWidth: '45%',
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                colors: ['#3b82f6', '#14b8a6', '#8b5cf6'],
                xaxis: {
                    categories: ['Pacientes', 'Medicamentos', 'Citas'],
                },
            }
        },
    },
}
</script>
