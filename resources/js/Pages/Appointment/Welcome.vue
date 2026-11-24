<template>
    <Head title="Citas" />

    <AuthenticatedLayout>
        <div class="space-y-20 pb-16 sm:space-y-24">
            <section
                class="relative -mx-4 overflow-hidden bg-white px-6 pb-16 pt-14 dark:bg-slate-950 sm:-mx-6 sm:rounded-3xl sm:px-10 sm:pb-20 sm:pt-16 lg:-mx-8 lg:px-16 lg:pt-20"
            >
                <div
                    class="pointer-events-none absolute -left-24 -top-32 h-96 w-96 rounded-full bg-brand-100/70 blur-3xl dark:bg-brand-500/10"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute -bottom-40 right-0 h-96 w-96 rounded-full bg-sky-100/70 blur-3xl dark:bg-sky-500/5"
                    aria-hidden="true"
                />

                <div class="relative mx-auto grid max-w-7xl gap-14 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:items-center">
                    <div>
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-brand-700 shadow-sm backdrop-blur dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-brand-500" aria-hidden="true" />
                            Software clínico · Citas
                        </span>

                        <h1
                            class="mt-7 text-4xl font-bold leading-[1.05] tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl"
                        >
                            Gestiona tus citas
                            <span class="block text-brand-600 dark:text-brand-400">de manera sencilla.</span>
                        </h1>

                        <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                            Organiza las consultas médicas, consulta la disponibilidad de los profesionales y mantén cada cita bajo control desde un solo lugar.
                        </p>

                        <div class="mt-9 flex flex-wrap items-center gap-4">
                            <Link :href="route('appointments.create')" class="ui-btn-primary px-6 py-3 text-sm shadow-md">
                                <UiIcon name="plus" size="h-4 w-4" />
                                Agendar cita
                            </Link>
                            <Link :href="route('agenda.index')" class="ui-btn-secondary px-6 py-3 text-sm shadow-md">
                                <UiIcon name="calendar" size="h-4 w-4" />
                                Ver calendario
                            </Link>
                        </div>

                        <div class="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-xs font-medium text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-2">
                                <UiIcon name="refresh" size="h-4 w-4" class="text-brand-600 dark:text-brand-400" />
                                Disponibilidad en tiempo real
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <UiIcon name="calendar-month" size="h-4 w-4" class="text-brand-600 dark:text-brand-400" />
                                Agenda centralizada
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <UiIcon name="user-md" size="h-4 w-4" class="text-brand-600 dark:text-brand-400" />
                                Acceso por rol
                            </span>
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            class="pointer-events-none absolute -inset-8 rounded-full bg-brand-100/80 blur-3xl dark:bg-brand-500/10"
                            aria-hidden="true"
                        />
                        <div
                            class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white shadow-2xl shadow-slate-900/10 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <img
                                src="/images/welcome-hero.svg"
                                alt="Profesional de salud gestionando citas en una tablet"
                                class="h-[320px] w-full object-cover object-center sm:h-[420px] lg:h-[480px]"
                            />
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-slate-900/40 to-transparent" />
                            <div
                                class="absolute left-5 top-5 inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm backdrop-blur dark:bg-slate-900/90 dark:text-slate-200"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true" />
                                Agenda en vivo
                            </div>
                        </div>

                        <div
                            class="relative z-10 -mt-12 ml-4 w-[calc(100%-2rem)] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900 sm:-mt-14 sm:ml-10 sm:w-80"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Próximos días
                                </p>
                                <Link
                                    :href="route('agenda.index')"
                                    class="text-[11px] font-semibold text-brand-600 hover:underline dark:text-brand-400"
                                >
                                    Ver agenda
                                </Link>
                            </div>

                            <div v-if="previewDays.length" class="mt-4 grid grid-cols-3 gap-2">
                                <div
                                    v-for="day in previewDays"
                                    :key="day.date"
                                    class="min-w-0 rounded-xl bg-slate-50 px-2 py-2.5 text-center dark:bg-slate-800/70"
                                >
                                    <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                        {{ day.weekday }}
                                    </p>
                                    <p class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white">{{ day.day }}</p>
                                    <p class="mt-1.5 truncate text-[10px] font-semibold text-brand-600 dark:text-brand-400">
                                        {{ day.appointments[0] ? formatTime(day.appointments[0].starttime) : '—' }}
                                    </p>
                                    <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">
                                        {{ day.appointments[0] ? fullName(day.appointments[0].patient) : 'Sin citas' }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="mt-4 rounded-xl bg-slate-50 px-3 py-4 text-center text-xs text-slate-500 dark:bg-slate-800/70 dark:text-slate-400">
                                No hay citas próximas registradas.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section aria-label="Beneficios del módulo" class="mx-auto max-w-6xl">
                <div class="grid divide-y divide-slate-200 border-y border-slate-200 py-8 dark:divide-slate-800 dark:border-slate-800 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div
                        v-for="item in valueItems"
                        :key="item.title"
                        class="flex items-start gap-4 px-1 py-5 dark:text-white sm:px-8 sm:py-0"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            <UiIcon :name="item.icon" size="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.title }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section aria-labelledby="quick-access-title" class="mx-auto max-w-7xl">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">
                        Todo en un solo lugar
                    </p>
                    <h2 id="quick-access-title" class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                        Las herramientas clave, a un clic.
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-500 dark:text-slate-400">
                        Accede a cada parte del flujo sin perder el contexto de tu agenda diaria.
                    </p>
                </div>

                <div class="mt-12 grid gap-x-10 gap-y-10 md:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="item in quickLinks"
                        :key="item.title"
                        :href="item.href"
                        class="group block border-t border-slate-200 pt-6 focus:outline-none dark:border-slate-800"
                    >
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition duration-200 group-hover:bg-brand-600 group-hover:text-white dark:bg-brand-500/10 dark:text-brand-300 dark:group-hover:bg-brand-500 dark:group-hover:text-white"
                        >
                            <UiIcon :name="item.icon" size="h-6 w-6" />
                        </span>
                        <span class="mt-6 flex items-center justify-between gap-3">
                            <span class="text-lg font-semibold text-slate-900 dark:text-white">{{ item.title }}</span>
                            <UiIcon
                                name="chevron-right"
                                size="h-5 w-5"
                                class="shrink-0 text-slate-300 transition group-hover:translate-x-1 group-hover:text-brand-600 dark:text-slate-600"
                            />
                        </span>
                        <span class="mt-2 block text-sm leading-6 text-slate-500 dark:text-slate-400">{{ item.description }}</span>
                        <span class="mt-4 block text-[11px] font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">
                            Acceder
                        </span>
                    </Link>
                </div>
            </section>

            <section
                class="relative -mx-4 overflow-hidden bg-brand-50/80 px-6 py-16 dark:bg-slate-900 sm:-mx-6 sm:rounded-3xl sm:px-10 lg:-mx-8 lg:px-16"
            >
                <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/70 blur-3xl dark:bg-brand-500/10" />
                <div class="pointer-events-none absolute -bottom-24 -left-16 h-64 w-64 rounded-full bg-sky-100/70 blur-3xl dark:bg-sky-500/5" />

                <div class="relative mx-auto max-w-7xl">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">
                                Actividad real
                            </p>
                            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                                Una agenda que se mueve contigo.
                            </h2>
                        </div>
                        <p class="max-w-md text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Cada cifra se calcula en MySQL con el mismo alcance que utiliza tu calendario.
                        </p>
                    </div>

                    <div class="mt-14 grid gap-12 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="item in statItems" :key="item.label" class="relative">
                            <span
                                class="pointer-events-none absolute -left-4 -top-6 h-20 w-20 rounded-full bg-white/70 blur-xl dark:bg-brand-500/10"
                                aria-hidden="true"
                            />
                            <p class="relative text-5xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-6xl">
                                {{ item.value }}
                            </p>
                            <p class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300">
                                {{ item.label }}
                            </p>
                            <p v-if="item.hint" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ item.hint }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:items-start" aria-labelledby="upcoming-title">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">
                        Agenda en movimiento
                    </p>
                    <h2 id="upcoming-title" class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                        Próximas citas, sin perder el detalle.
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-500 dark:text-slate-400">
                        Paciente, profesional, especialidad y estado en un solo vistazo.
                    </p>

                    <label v-if="isAdmin" class="mt-7 block max-w-xs">
                        <span class="sr-only">Filtrar por médico</span>
                        <select :value="filters.doctor_id ?? ''" class="ui-input" @change="changeDoctor">
                            <option value="">Todos los médicos</option>
                            <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                {{ fullName(doctor) }}
                            </option>
                        </select>
                    </label>

                    <div class="mt-9 overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <img
                            src="/images/welcome-care.svg"
                            alt="Paciente registrándose en el mostrador de una clínica"
                            class="h-56 w-full object-cover object-center"
                            loading="lazy"
                        />
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-400 dark:text-slate-500">
                        Atención y seguimiento desde un mismo lugar.
                    </p>

                    <Link
                        :href="route('agenda.index')"
                        class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-brand-600 hover:underline dark:text-brand-400"
                    >
                        Ver calendario completo
                        <UiIcon name="chevron-right" size="h-4 w-4" />
                    </Link>
                </div>

                <div v-if="upcomingAppointments.length" class="divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    <div
                        v-for="appointment in upcomingAppointments"
                        :key="appointment.id"
                        class="group flex flex-col gap-4 py-5 sm:flex-row sm:items-center sm:gap-6"
                    >
                        <span
                            class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300"
                        >
                            <span class="text-[9px] font-bold uppercase tracking-wide">{{ formatWeekday(appointment.date) }}</span>
                            <span class="text-lg font-bold leading-none">{{ dayNumber(appointment.date) }}</span>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-semibold text-slate-900 dark:text-white">
                                {{ fullName(appointment.patient) }}
                            </p>
                            <p class="mt-0.5 truncate text-sm text-slate-500 dark:text-slate-400">
                                Dr. {{ fullName(appointment.doctor) }} · {{ appointment.specialty }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 sm:w-auto sm:justify-end">
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                {{ formatShortDate(appointment.date) }} · {{ timeRangeLabel(appointment.starttime, appointment.endtime) }}
                            </span>
                            <StatusBadge :status="appointment.status" />
                            <Link
                                v-if="appointment.can?.view"
                                :href="route('appointments.show', appointment.id)"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:underline dark:text-brand-400"
                                :aria-label="`Ver detalle de la cita de ${fullName(appointment.patient)}`"
                            >
                                <UiIcon name="eye" size="h-4 w-4" />
                                Detalle
                            </Link>
                        </div>
                    </div>
                </div>

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

            <section
                id="como-funciona"
                class="relative -mx-4 overflow-hidden bg-slate-900 px-6 py-16 text-white dark:bg-slate-950 sm:-mx-6 sm:rounded-3xl sm:px-10 lg:-mx-8 lg:px-16"
            >
                <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true" />
                <div class="pointer-events-none absolute -bottom-32 right-0 h-80 w-80 rounded-full bg-sky-500/10 blur-3xl" aria-hidden="true" />

                <div class="relative mx-auto max-w-6xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-300">Cómo funciona</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                        Cuatro pasos para programar una cita
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-slate-300">
                        Del paciente seleccionado al horario confirmado, sin perder de vista la agenda.
                    </p>

                    <ol class="mt-14 grid gap-10 text-start md:grid-cols-4">
                        <li v-for="(step, index) in steps" :key="step.title" class="relative">
                            <span
                                v-if="index < steps.length - 1"
                                class="absolute left-14 right-[-1.5rem] top-6 hidden border-t border-dashed border-white/20 md:block"
                                aria-hidden="true"
                            />
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-base font-bold ring-1 ring-white/20"
                            >
                                {{ index + 1 }}
                            </span>
                            <UiIcon :name="step.icon" size="h-6 w-6" class="mt-6 text-brand-300" />
                            <h3 class="mt-3 text-base font-semibold text-white">{{ step.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-300">{{ step.description }}</p>
                        </li>
                    </ol>

                    <div class="mt-12">
                        <Link :href="route('appointments.create')" class="ui-btn-primary px-6 py-3">
                            Comenzar
                            <UiIcon name="chevron-right" size="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl" aria-labelledby="final-title">
                <div
                    class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white px-6 py-12 dark:border-slate-800 dark:bg-slate-900 sm:px-12"
                >
                    <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-brand-100/70 blur-3xl dark:bg-brand-500/10" />
                    <div class="pointer-events-none absolute -bottom-20 left-1/3 h-52 w-52 rounded-full bg-sky-100/70 blur-3xl dark:bg-sky-500/5" />

                    <div class="relative flex flex-col gap-8 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">
                                Listo para empezar
                            </p>
                            <h2 id="final-title" class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                Tu próxima cita empieza aquí.
                            </h2>
                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Registra la consulta o abre el calendario para revisar la disponibilidad de los profesionales.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
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
            valueItems: [
                {
                    title: 'Disponibilidad en tiempo real',
                    description: 'Cada horario se calcula con sesiones, descansos y citas registradas.',
                    icon: 'refresh',
                },
                {
                    title: 'Agenda centralizada',
                    description: 'Consulta el día, la semana y el mes sin salir del módulo.',
                    icon: 'calendar-month',
                },
                {
                    title: 'Información protegida',
                    description: 'Administradores y médicos ven solo lo que su rol autoriza.',
                    icon: 'user-md',
                },
            ],
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
                { label: 'Citas de hoy', value: this.stats.today ?? 0 },
                { label: 'Citas pendientes', value: this.stats.pending ?? 0 },
                { label: 'Citas confirmadas', value: this.stats.confirmed ?? 0 },
                { label: 'Médicos disponibles', value: this.stats.availableDoctors ?? 0, hint: 'Con horario libre hoy' },
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
                appointments,
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
