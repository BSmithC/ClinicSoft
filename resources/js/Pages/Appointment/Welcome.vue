<template>
    <Head title="Citas" />

    <AuthenticatedLayout>
        <div class="space-y-12 pb-6">
            <section
                class="relative overflow-hidden rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-sky-50 px-6 py-10 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 sm:px-10 lg:px-12 lg:py-14"
            >
                <div
                    class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-brand-100/70 blur-3xl dark:bg-brand-500/10"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-sky-100/60 blur-3xl dark:bg-sky-500/5"
                    aria-hidden="true"
                />

                <div class="relative grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)] lg:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">
                            Módulo de citas
                        </p>
                        <h1
                            class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl lg:text-[2.6rem] lg:leading-tight"
                        >
                            Gestiona tus citas de manera sencilla.
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                            Organiza las consultas médicas, consulta la disponibilidad de los profesionales y mantén cada cita bajo control desde un solo lugar.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link :href="route('appointments.create')" class="ui-btn-primary px-5 py-2.5">
                                <UiIcon name="plus" size="h-4 w-4" />
                                Agendar cita
                            </Link>
                            <Link :href="route('agenda.index')" class="ui-btn-secondary px-5 py-2.5">
                                <UiIcon name="calendar" size="h-4 w-4" />
                                Ver calendario
                            </Link>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="ui-card overflow-hidden shadow-card-hover">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                        <UiIcon name="calendar-month" size="h-4 w-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Vista previa de tu agenda</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Próximos días con citas registradas</p>
                                    </div>
                                </div>
                                <Link
                                    :href="route('agenda.index')"
                                    class="shrink-0 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                                >
                                    Abrir
                                </Link>
                            </div>

                            <div v-if="previewDays.length" class="grid gap-3 p-5 sm:grid-cols-3">
                                <div
                                    v-for="day in previewDays"
                                    :key="day.date"
                                    class="min-w-0 rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-800/30"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            {{ day.weekday }}
                                        </span>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ day.day }}</span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ day.label }}</p>

                                    <div class="mt-3 space-y-2">
                                        <div
                                            v-for="appointment in day.appointments"
                                            :key="appointment.id"
                                            class="rounded-lg border-l-2 border-brand-500 bg-white px-2 py-1.5 shadow-sm dark:bg-slate-900"
                                        >
                                            <p class="text-[11px] font-semibold text-brand-600 dark:text-brand-400">
                                                {{ formatTime(appointment.starttime) }}
                                            </p>
                                            <p class="truncate text-[11px] text-slate-700 dark:text-slate-200">
                                                {{ fullName(appointment.patient) }}
                                            </p>
                                        </div>
                                        <p v-if="day.extra" class="text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                            +{{ day.extra }} más
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center justify-center px-6 py-12 text-center">
                                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                    <UiIcon name="calendar" size="h-5 w-5" />
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-white">Sin citas próximas</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Cuando registres una cita aparecerá aquí.
                                </p>
                            </div>

                            <div class="flex items-center gap-2 border-t border-slate-100 bg-white px-5 py-3 text-[11px] text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                                <UiIcon name="information" size="h-3.5 w-3.5" />
                                Información tomada directamente de la agenda.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section aria-labelledby="quick-access-title">
                <div class="mb-5">
                    <h2 id="quick-access-title" class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Accesos rápidos
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Entra directamente a las acciones que más usas.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="item in quickLinks"
                        :key="item.title"
                        :href="item.href"
                        class="ui-card group flex items-start gap-4 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-card-hover focus:outline-none focus:ring-2 focus:ring-brand-500 dark:hover:border-brand-800"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:group-hover:bg-brand-500/20">
                            <UiIcon :name="item.icon" size="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.title }}</span>
                                <UiIcon
                                    name="chevron-right"
                                    size="h-4 w-4"
                                    class="shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500 dark:text-slate-600"
                                />
                            </span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ item.description }}</span>
                        </span>
                    </Link>
                </div>
            </section>

            <section class="ui-card overflow-hidden" aria-labelledby="activity-title">
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h2 id="activity-title" class="text-base font-semibold text-slate-900 dark:text-white">Resumen de actividad</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Datos actuales de la agenda</p>
                    </div>
                    <UiIcon name="chart" size="h-5 w-5" class="text-slate-300 dark:text-slate-600" />
                </div>

                <div class="grid divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
                    <div v-for="item in statItems" :key="item.label" class="flex items-center gap-4 p-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="item.tone">
                            <UiIcon :name="item.icon" size="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                {{ item.label }}
                            </p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ item.value }}</p>
                            <p v-if="item.hint" class="mt-0.5 truncate text-[11px] text-slate-400 dark:text-slate-500">{{ item.hint }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ui-card overflow-hidden" aria-labelledby="upcoming-title">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="upcoming-title" class="text-base font-semibold text-slate-900 dark:text-white">Próximas citas</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Agenda a partir de hoy</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <label v-if="isAdmin" class="flex items-center gap-2">
                            <span class="sr-only">Filtrar por médico</span>
                            <select :value="filters.doctor_id ?? ''" class="ui-input w-auto py-1.5 text-xs" @change="changeDoctor">
                                <option value="">Todos los médicos</option>
                                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                    {{ fullName(doctor) }}
                                </option>
                            </select>
                        </label>
                        <Link
                            :href="route('agenda.index')"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400"
                        >
                            Abrir calendario
                            <UiIcon name="chevron-right" size="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <ul v-if="upcomingAppointments.length" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <li
                        v-for="appointment in upcomingAppointments"
                        :key="appointment.id"
                        class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40 sm:flex-row sm:items-center"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-1 ring-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20"
                            >
                                <span class="text-[9px] font-semibold uppercase tracking-wide">{{ formatWeekday(appointment.date) }}</span>
                                <span class="text-sm font-bold leading-none">{{ dayNumber(appointment.date) }}</span>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ fullName(appointment.patient) }}
                                </p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                    Dr. {{ fullName(appointment.doctor) }} · {{ appointment.specialty }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-wrap items-center gap-3 sm:justify-end">
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                {{ formatShortDate(appointment.date) }} · {{ timeRangeLabel(appointment.starttime, appointment.endtime) }}
                            </span>
                            <StatusBadge :status="appointment.status" />
                            <Link
                                v-if="appointment.can?.view"
                                :href="route('appointments.show', appointment.id)"
                                class="ui-btn-ghost px-2 py-1 text-xs"
                                :aria-label="`Ver detalle de la cita de ${fullName(appointment.patient)}`"
                            >
                                <UiIcon name="eye" size="h-4 w-4" />
                                Detalle
                            </Link>
                        </div>
                    </li>
                </ul>

                <EmptyState
                    v-else
                    title="Sin citas próximas"
                    description="No hay citas activas programadas para los próximos días."
                    icon="calendar"
                >
                    <template #actions>
                        <Link :href="route('appointments.create')" class="ui-btn-primary">Agendar cita</Link>
                    </template>
                </EmptyState>
            </section>

            <section class="ui-card p-6 sm:p-8" aria-labelledby="how-it-works-title">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">Cómo funciona</p>
                    <h2 id="how-it-works-title" class="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Cuatro pasos para programar una cita
                    </h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Del paciente seleccionado al horario confirmado, sin perder de vista la agenda.
                    </p>
                </div>

                <ol class="mt-8 grid gap-6 md:grid-cols-4">
                    <li v-for="(step, index) in steps" :key="step.title" class="relative flex gap-4 md:block">
                        <span
                            v-if="index < steps.length - 1"
                            class="absolute left-10 right-[-1.5rem] top-5 hidden border-t border-dashed border-slate-200 dark:border-slate-700 md:block"
                            aria-hidden="true"
                        />
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-brand-600 ring-1 ring-brand-200 dark:bg-slate-900 dark:text-brand-300 dark:ring-brand-800"
                        >
                            {{ index + 1 }}
                        </span>
                        <div class="min-w-0 md:mt-4">
                            <UiIcon :name="step.icon" size="h-5 w-5" class="text-brand-600 dark:text-brand-400" />
                            <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ step.title }}</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ step.description }}</p>
                        </div>
                    </li>
                </ol>

                <div class="mt-8">
                    <Link :href="route('appointments.create')" class="ui-btn-primary">
                        Comenzar
                        <UiIcon name="chevron-right" size="h-4 w-4" />
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UiIcon from '@/Components/UiIcon.vue';
import { dayNumber, formatShortDate, formatTime, formatWeekday, timeRangeLabel } from '@/utils/calendar';

export default {
    name: 'AppointmentWelcome',
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        EmptyState,
        StatusBadge,
        UiIcon,
    },
    props: {
        stats: { type: Object, required: true },
        upcomingAppointments: { type: Array, default: () => [] },
        doctors: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({ doctor_id: null }) },
        isAdmin: { type: Boolean, default: false },
    },
    data() {
        return {
            quickLinks: [
                {
                    title: 'Nueva cita',
                    description: 'Registra una consulta y reserva el horario.',
                    icon: 'plus',
                    href: route('appointments.create'),
                },
                {
                    title: 'Calendario médico',
                    description: 'Revisa la agenda por día, semana o mes.',
                    icon: 'calendar-month',
                    href: route('agenda.index'),
                },
                {
                    title: 'Pacientes',
                    description: 'Consulta y gestiona las fichas de pacientes.',
                    icon: 'users',
                    href: route('patients.index'),
                },
                {
                    title: 'Médicos',
                    description: 'Revisa profesionales y especialidades.',
                    icon: 'user-md',
                    href: route('doctors.index'),
                },
            ],
            steps: [
                { title: 'Seleccionar paciente', description: 'Elige la ficha del paciente que será atendido.', icon: 'users' },
                { title: 'Elegir médico', description: 'Selecciona al profesional y su especialidad.', icon: 'user-md' },
                { title: 'Consultar horarios', description: 'Revisa los espacios disponibles en la agenda.', icon: 'clock' },
                { title: 'Confirmar cita', description: 'Registra la cita y mantén el control del estado.', icon: 'check' },
            ],
        };
    },
    computed: {
        statItems() {
            return [
                {
                    label: 'Citas de hoy',
                    value: this.stats.today ?? 0,
                    icon: 'today',
                    tone: 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
                },
                {
                    label: 'Citas pendientes',
                    value: this.stats.pending ?? 0,
                    icon: 'clock',
                    tone: 'bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-300',
                },
                {
                    label: 'Citas confirmadas',
                    value: this.stats.confirmed ?? 0,
                    icon: 'check',
                    tone: 'bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-300',
                },
                {
                    label: 'Médicos disponibles',
                    value: this.stats.availableDoctors ?? 0,
                    icon: 'user-md',
                    tone: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-300',
                    hint: 'Con horario libre hoy',
                },
            ];
        },
        previewDays() {
            const byDate = new Map();

            this.upcomingAppointments.forEach((appointment) => {
                if (!byDate.has(appointment.date)) {
                    byDate.set(appointment.date, []);
                }
                byDate.get(appointment.date).push(appointment);
            });

            return [...byDate.entries()].slice(0, 3).map(([date, appointments]) => ({
                date,
                weekday: formatWeekday(date),
                day: dayNumber(date),
                label: formatShortDate(date),
                appointments: appointments.slice(0, 2),
                extra: Math.max(0, appointments.length - 2),
            }));
        },
    },
    methods: {
        fullName(person) {
            if (!person) {
                return '';
            }
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim();
        },
        formatTime,
        formatWeekday,
        dayNumber,
        formatShortDate,
        timeRangeLabel,
        changeDoctor(event) {
            const doctorId = event.target.value || null;

            router.get(
                route('appointments.welcome'),
                doctorId ? { doctor_id: doctorId } : {},
                { preserveState: true, preserveScroll: true }
            );
        },
    },
};
</script>
