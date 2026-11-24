<template>
    <Head :title="`Dr. ${doctorName}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Gestión clínica"
                :title="`Dr. ${doctorName}`"
                :description="`${doctor.user?.email ?? 'Sin correo'} · Licencia ${doctor.license_number || '—'}`"
            >
                <template #actions>
                    <Link :href="route('doctors.agenda', doctor.id)" class="ui-btn-primary">
                        <UiIcon name="calendar" size="h-4 w-4" />
                        Ver agenda
                    </Link>
                    <Link :href="route('doctors.edit', doctor.id)" class="ui-btn-secondary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                    <Link :href="route('doctors.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="ui-card p-6">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Perfil</h2>
                <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                    {{ doctor.bio || 'Sin biografía registrada.' }}
                </p>
                <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">Especialidades</p>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="specialty in doctor.specialties"
                        :key="specialty.id"
                        class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20"
                    >
                        {{ specialty.name }}
                    </span>
                    <span v-if="!doctor.specialties?.length" class="text-sm text-slate-500 dark:text-slate-400">
                        Sin especialidades asignadas.
                    </span>
                </div>
            </section>

            <section class="ui-card p-6">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Próximas sesiones</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="session in sessions" :key="session.id" class="border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800">
                        <Link :href="route('sessions.show', session.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            {{ session.title }}
                        </Link>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }} · {{ session.specialty?.name ?? 'Sin especialidad' }}
                        </p>
                    </li>
                    <li v-if="!sessions.length" class="text-slate-500 dark:text-slate-400">No hay sesiones recientes.</li>
                </ul>
            </section>

            <section class="ui-card p-6">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Últimas citas</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="appointment in appointments" :key="appointment.id" class="border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800">
                        <Link :href="route('appointments.show', appointment.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            Cita #{{ appointment.id }}
                        </Link>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ formatDate(appointment.date) }} · {{ fullName(appointment.patient) }}
                        </p>
                        <div class="mt-1"><StatusBadge :status="appointment.status" /></div>
                    </li>
                    <li v-if="!appointments.length" class="text-slate-500 dark:text-slate-400">No hay citas recientes.</li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        StatusBadge,
        UiIcon,
    },
    props: {
        doctor: { type: Object, required: true },
        sessions: { type: Array, default: () => [] },
        appointments: { type: Array, default: () => [] },
    },
    computed: {
        doctorName() {
            const user = this.doctor.user;
            if (!user) return `#${this.doctor.id}`;
            return `${user.name ?? ''} ${user.last_name ?? ''}`.trim();
        },
    },
    methods: {
        fullName(person) {
            if (!person) return '—';
            return `${person.name ?? ''} ${person.last_name ?? ''}`.trim() || '—';
        },
        formatDate(dateString) {
            if (!dateString) return '—';
            return String(dateString).slice(0, 10);
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '—';
        },
    },
};
</script>
