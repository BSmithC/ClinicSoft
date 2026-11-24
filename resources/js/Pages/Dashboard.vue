<template>
    <Head title="Panel general" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Resumen clínico"
                title="Panel general"
                :description="isAdmin
                    ? 'Indicadores globales de la operación, calculados en tiempo real desde la base de datos.'
                    : 'Indicadores de tu consulta, calculados en tiempo real desde la base de datos.'"
            >
                <template #actions>
                    <Link :href="route('agenda.index')" class="ui-btn-secondary">
                        <UiIcon name="calendar" size="h-4 w-4" />
                        Ver agenda
                    </Link>
                    <Link :href="route('appointments.create')" class="ui-btn-primary">
                        <UiIcon name="plus" size="h-4 w-4" />
                        Nueva cita
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Indicadores principales">
                <StatCard v-for="card in statCards" :key="card.label" v-bind="card" />
            </section>

            <section class="grid gap-6 lg:grid-cols-3">
                <article class="ui-card flex flex-col p-5 lg:col-span-2">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Citas por estado</h2>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Distribución real de las citas registradas</p>
                        </div>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            <UiIcon name="chart" size="h-4 w-4" />
                        </span>
                    </div>

                    <apexchart type="bar" height="280" :options="chartOptions" :series="series" />

                    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 sm:grid-cols-4">
                        <div v-for="item in miniStats" :key="item.label">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ item.label }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-800 dark:text-white">{{ item.value }}</p>
                        </div>
                    </div>
                </article>

                <article class="ui-card flex flex-col">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Próximas citas</h2>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Agenda a partir de hoy</p>
                        </div>
                        <Link :href="route('appointments.index')" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            Ver todas
                        </Link>
                    </div>

                    <ul v-if="upcomingAppointments.length" class="flex-1 divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="appointment in upcomingAppointments" :key="appointment.id" class="px-5 py-3.5 transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                            <Link :href="route('appointments.show', appointment.id)" class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ initials(appointment.patient) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-800 dark:text-white">
                                        {{ fullName(appointment.patient) }}
                                    </span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        {{ formatDate(appointment.date) }} · {{ formatTime(appointment.starttime) }}
                                    </span>
                                </span>
                                <StatusBadge :status="appointment.status" />
                            </Link>
                        </li>
                    </ul>
                    <EmptyState
                        v-else
                        title="Sin citas próximas"
                        description="No hay citas activas programadas para los próximos días."
                        icon="calendar"
                    />
                </article>
            </section>

            <section class="ui-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Actividad reciente</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Últimas operaciones registradas en el sistema</p>
                    </div>
                </div>
                <ul v-if="recentActivity.length" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li v-for="log in recentActivity" :key="log.id" class="flex items-center gap-3 px-5 py-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            <UiIcon :name="activityIcon(log.action)" size="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 dark:text-white">{{ activityLabel(log.action) }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ log.user?.name ?? 'Sistema' }} · {{ log.subject_type }} #{{ log.subject_id }}
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400 dark:text-slate-500">{{ formatDateTime(log.created_at) }}</span>
                    </li>
                </ul>
                <EmptyState v-else title="Sin actividad" description="Aún no se han registrado operaciones." icon="clock" />
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import UiIcon from '@/Components/UiIcon.vue';
import ApexChart from 'vue3-apexcharts';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        StatCard,
        StatusBadge,
        EmptyState,
        UiIcon,
        apexchart: ApexChart,
    },
    props: {
        Kpis: { type: Object, required: true },
        statusBreakdown: { type: Object, default: () => ({}) },
        upcomingAppointments: { type: Array, default: () => [] },
        recentActivity: { type: Array, default: () => [] },
        isAdmin: { type: Boolean, default: false },
    },
    data() {
        return {
            darkMode: false,
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
            activityIcons: {
                'appointment.booked': 'calendar',
                'appointment.cancelled': 'close',
                'appointment.status_changed': 'check',
                'session.cancelled': 'clock',
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
        statCards() {
            const kpis = this.Kpis;
            return [
                {
                    label: this.isAdmin ? 'Pacientes' : 'Mis pacientes',
                    value: kpis.PatientsAll ?? 0,
                    icon: 'users',
                    tone: 'brand',
                    hint: this.isAdmin ? 'Registrados en el sistema' : 'Con citas en mi agenda',
                    href: route('patients.index'),
                },
                {
                    label: 'Médicos activos',
                    value: kpis.ActiveDoctors ?? 0,
                    icon: 'user',
                    tone: 'sky',
                    hint: `${kpis.RegisteredDoctors ?? 0} perfiles registrados`,
                    href: route('doctors.index'),
                },
                {
                    label: this.isAdmin ? 'Citas de hoy' : 'Mis citas de hoy',
                    value: kpis.AppointmentsToday ?? 0,
                    icon: 'calendar',
                    tone: 'brand',
                    hint: 'Vigentes, sin cancelar',
                    href: route('agenda.index'),
                },
                {
                    label: 'Pendientes',
                    value: kpis.PendingAppointments ?? 0,
                    icon: 'clock',
                    tone: 'amber',
                    hint: 'Requieren confirmación',
                    href: route('appointments.index'),
                },
                {
                    label: 'Completadas',
                    value: kpis.CompletedAppointments ?? 0,
                    icon: 'check',
                    tone: 'emerald',
                    hint: 'Atención finalizada',
                    href: route('appointments.index'),
                },
                {
                    label: 'Próximas',
                    value: kpis.UpcomingAppointments ?? 0,
                    icon: 'calendar',
                    tone: 'violet',
                    hint: 'Citas activas futuras',
                    href: route('appointments.index'),
                },
            ];
        },
        miniStats() {
            return [
                { label: 'Confirmadas', value: this.Kpis.ConfirmedAppointments ?? 0 },
                { label: 'Canceladas', value: this.Kpis.CancelledAppointments ?? 0 },
                { label: 'Sesiones activas', value: this.Kpis.ActiveSessions ?? 0 },
                { label: this.isAdmin ? 'Facturación' : 'Facturación asociada', value: this.totalBilling },
            ];
        },
        series() {
            return [
                {
                    name: 'Citas',
                    data: Object.values(this.statusBreakdown),
                },
            ];
        },
        chartOptions() {
            const labelColor = this.darkMode ? '#94a3b8' : '#64748b';
            return {
                chart: {
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'Figtree, sans-serif',
                    parentHeightOffset: 0,
                },
                theme: { mode: this.darkMode ? 'dark' : 'light' },
                xaxis: {
                    categories: Object.keys(this.statusBreakdown).map((key) => this.statusLabels[key] ?? key),
                    labels: { rotate: -30, trim: true, style: { colors: labelColor, fontSize: '11px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: { style: { colors: labelColor, fontSize: '11px' } },
                },
                grid: {
                    borderColor: this.darkMode ? '#1e293b' : '#e2e8f0',
                    strokeDashArray: 4,
                    padding: { top: 0, right: 8 },
                },
                colors: ['#f59e0b', '#0ea5e9', '#8b5cf6', '#6366f1', '#10b981', '#f43f5e', '#64748b'],
                dataLabels: { enabled: false },
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 6 } },
                legend: { show: false },
            };
        },
    },
    mounted() {
        this.darkMode = document.documentElement.classList.contains('dark');
        window.addEventListener('theme-changed', this.onThemeChanged);
    },
    beforeUnmount() {
        window.removeEventListener('theme-changed', this.onThemeChanged);
    },
    methods: {
        onThemeChanged(event) {
            this.darkMode = Boolean(event.detail?.dark);
        },
        fullName(person) {
            if (!person) return 'Paciente no disponible';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim();
        },
        initials(person) {
            if (!person) return '—';
            const first = person.name?.[0] ?? '';
            const last = person.last_name?.[0] ?? '';
            return `${first}${last}`.toUpperCase() || '—';
        },
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
        formatDateTime(value) {
            if (!value) return '—';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return new Intl.DateTimeFormat('es-ES', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            }).format(date);
        },
        activityLabel(action) {
            return this.activityLabels[action] ?? action;
        },
        activityIcon(action) {
            return this.activityIcons[action] ?? 'information';
        },
    },
};
</script>
