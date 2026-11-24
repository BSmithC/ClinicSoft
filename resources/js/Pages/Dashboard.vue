<template>
    <Head title="Panel general" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Panel general</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Indicadores calculados en tiempo real desde la base de datos.</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('agenda.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white dark:border-slate-600 dark:text-slate-200">Ver agenda</Link>
                    <Link :href="route('appointments.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nueva cita</Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                <CardStats statSubtitle="Pacientes" :statTitle="Kpis.PatientsAll" statIconName="fas fa-user-injured" statIconColor="bg-blue-500" :show-trend="false" />
                <CardStats statSubtitle="Médicos" :statTitle="Kpis.ActiveDoctors" statIconName="fas fa-user-md" statIconColor="bg-indigo-500" :show-trend="false" />
                <CardStats statSubtitle="Especialidades" :statTitle="Kpis.SpecialtiesAll" statIconName="fas fa-stethoscope" statIconColor="bg-violet-500" :show-trend="false" />
                <CardStats statSubtitle="Citas de hoy" :statTitle="Kpis.AppointmentsToday" statIconName="fas fa-calendar-day" statIconColor="bg-sky-500" :show-trend="false" />
                <CardStats statSubtitle="Pendientes" :statTitle="Kpis.PendingAppointments" statIconName="fas fa-hourglass-half" statIconColor="bg-amber-500" :show-trend="false" />
                <CardStats statSubtitle="Confirmadas" :statTitle="Kpis.ConfirmedAppointments" statIconName="fas fa-calendar-check" statIconColor="bg-cyan-500" :show-trend="false" />
                <CardStats statSubtitle="Completadas" :statTitle="Kpis.CompletedAppointments" statIconName="fas fa-circle-check" statIconColor="bg-emerald-500" :show-trend="false" />
                <CardStats statSubtitle="Canceladas" :statTitle="Kpis.CancelledAppointments" statIconName="fas fa-ban" statIconColor="bg-rose-500" :show-trend="false" />
                <CardStats statSubtitle="Próximas" :statTitle="Kpis.UpcomingAppointments" statIconName="fas fa-calendar-arrow-up" statIconColor="bg-fuchsia-500" :show-trend="false" />
                <CardStats statSubtitle="Sesiones activas" :statTitle="Kpis.ActiveSessions" statIconName="fas fa-clock" statIconColor="bg-teal-500" :show-trend="false" />
                <CardStats statSubtitle="Medicamentos" :statTitle="Kpis.DrugsAll" statIconName="fas fa-pills" statIconColor="bg-lime-500" :show-trend="false" />
                <CardStats statSubtitle="Facturación" :statTitle="totalBilling" statIconName="fas fa-file-invoice-dollar" statIconColor="bg-emerald-600" :show-trend="false" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                    <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Citas por estado</h2>
                    <apexchart type="bar" height="300" :options="chartOptions" :series="series" />
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-800 dark:text-white">Próximas citas</h2>
                        <Link :href="route('appointments.index')" class="text-xs font-semibold text-blue-600 hover:underline">Ver todas</Link>
                    </div>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="appointment in upcomingAppointments" :key="appointment.id" class="py-3">
                            <Link :href="route('appointments.show', appointment.id)" class="flex items-center justify-between gap-3 hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800 dark:text-white">
                                        {{ appointment.patient?.name }} {{ appointment.patient?.last_name }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ formatDate(appointment.date) }} · {{ formatTime(appointment.starttime) }}</p>
                                </div>
                                <StatusBadge :status="appointment.status" />
                            </Link>
                        </li>
                        <li v-if="!upcomingAppointments.length" class="py-6 text-center text-sm text-slate-500">No hay citas próximas.</li>
                    </ul>
                </section>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Actividad reciente</h2>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="log in recentActivity" :key="log.id" class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ activityLabel(log.action) }}</p>
                            <p class="text-xs text-slate-500">{{ log.user?.name ?? 'Sistema' }} · {{ log.subject_type }} #{{ log.subject_id }}</p>
                        </div>
                        <span class="text-xs text-slate-400">{{ log.created_at }}</span>
                    </li>
                    <li v-if="!recentActivity.length" class="py-6 text-center text-sm text-slate-500">Aún no hay operaciones registradas.</li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import CardStats from '@/Components/CardStats.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ApexChart from 'vue3-apexcharts';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        CardStats,
        StatusBadge,
        apexchart: ApexChart,
    },
    props: {
        Kpis: {
            type: Object,
            required: true,
        },
        statusBreakdown: {
            type: Object,
            default: () => ({}),
        },
        upcomingAppointments: {
            type: Array,
            default: () => [],
        },
        recentActivity: {
            type: Array,
            default: () => [],
        },
        isAdmin: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            statusLabels: {
                pending: 'Pendientes',
                confirmed: 'Confirmadas',
                waiting: 'En espera',
                in_consultation: 'En consulta',
                completed: 'Completadas',
                cancelled: 'Canceladas',
                no_show: 'No asistió',
            },
            activityLabels: {
                'appointment.booked': 'Cita registrada',
                'appointment.cancelled': 'Cita cancelada',
                'appointment.status_changed': 'Estado actualizado',
                'session.cancelled': 'Sesión cancelada',
            },
        };
    },
    computed: {
        totalBilling() {
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'DOP',
                maximumFractionDigits: 2,
            }).format(Number(this.Kpis.TotalBilling || 0));
        },
        series() {
            return [
                {
                    name: 'Citas',
                    data: Object.entries(this.statusBreakdown).map(([, value]) => value),
                },
            ];
        },
        chartOptions() {
            return {
                chart: { toolbar: { show: false }, zoom: { enabled: false } },
                xaxis: {
                    categories: Object.keys(this.statusBreakdown).map((key) => this.statusLabels[key] ?? key),
                    labels: { rotate: -30, trim: true },
                },
                colors: ['#f59e0b', '#0ea5e9', '#8b5cf6', '#6366f1', '#10b981', '#f43f5e', '#64748b'],
                dataLabels: { enabled: false },
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 6 } },
            };
        },
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return '—';
            return new Intl.DateTimeFormat('es-ES', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }).format(new Date(`${String(dateString).slice(0, 10)}T12:00:00`));
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '—';
        },
        activityLabel(action) {
            return this.activityLabels[action] ?? action;
        },
    },
};
</script>
