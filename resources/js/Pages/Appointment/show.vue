<template>
    <Head title="Detalle de cita" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Cita #{{ appointment.id }}</h1>
                <StatusBadge :status="appointment.status" />
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
                <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Información de la cita</h2>
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Paciente</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ appointment.patient?.name }} {{ appointment.patient?.last_name }}</dd></div>
                    <div><dt class="text-slate-500">Médico</dt><dd class="font-semibold text-slate-800 dark:text-white">Dr. {{ appointment.doctor?.name }} {{ appointment.doctor?.last_name }}</dd></div>
                    <div><dt class="text-slate-500">Fecha</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ formatDate(appointment.date) }}</dd></div>
                    <div><dt class="text-slate-500">Horario</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ formatTime(appointment.starttime) }}–{{ formatTime(appointment.endtime) }}</dd></div>
                    <div><dt class="text-slate-500">Sesión</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ appointment.session?.title ?? 'Cita independiente' }}</dd></div>
                    <div><dt class="text-slate-500">Especialidad</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ appointment.specialty?.name ?? appointment.session?.specialty?.name ?? 'No especificada' }}</dd></div>
                    <div><dt class="text-slate-500">Medicamento</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ appointment.drug?.name ?? 'No especificado' }}</dd></div>
                    <div><dt class="text-slate-500">Asistencia</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ appointment.attended ? 'Sí' : 'No' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Motivo</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ appointment.title }}</dd></div>
                    <div v-if="appointment.notes" class="sm:col-span-2"><dt class="text-slate-500">Notas</dt><dd class="text-slate-700 dark:text-slate-200">{{ appointment.notes }}</dd></div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <Link :href="route('appointments.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver</Link>
                    <Link :href="route('appointments.edit', appointment.id)" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Editar</Link>
                    <Link :href="route('appointments.history', appointment.id)" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Historial</Link>
                    <button
                        v-if="statusValue !== 'cancelled' && statusValue !== 'completed' && statusValue !== 'no_show'"
                        type="button"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                        @click="cancelAppointment"
                    >Cancelar cita</button>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Cambiar estado</h2>
                    <div v-if="transitions.length" class="space-y-3">
                        <select v-model="nextStatus" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option disabled value="">Seleccione una transición</option>
                            <option v-for="transition in transitions" :key="transition.value" :value="transition.value">{{ transition.label }}</option>
                        </select>
                        <input v-model="reason" type="text" placeholder="Motivo (opcional)" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <button
                            type="button"
                            :disabled="!nextStatus"
                            class="w-full rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 hover:bg-sky-700"
                            @click="changeStatus"
                        >Aplicar estado</button>
                    </div>
                    <p v-else class="text-sm text-slate-500">La cita está en un estado final; no admite más transiciones.</p>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Últimos movimientos</h2>
                    <ul class="space-y-3 text-sm">
                        <li v-for="history in appointment.histories?.slice(-4).reverse()" :key="history.id" class="border-b border-slate-100 pb-2 last:border-0 dark:border-slate-800">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ history.to_status }}</p>
                            <p class="text-xs text-slate-500">{{ history.created_at }} · {{ history.actor?.name ?? 'Sistema' }}</p>
                        </li>
                        <li v-if="!appointment.histories?.length" class="text-slate-500">Sin movimientos registrados.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        StatusBadge,
    },
    props: {
        appointment: {
            type: Object,
            required: true,
        },
        statuses: {
            type: Array,
            default: () => [],
        },
        transitions: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            nextStatus: '',
            reason: '',
        };
    },
    computed: {
        statusValue() {
            const status = this.appointment.status;
            return status && typeof status === 'object' ? status.value : status || 'pending';
        },
    },
    methods: {
        changeStatus() {
            if (!this.nextStatus) return;
            router.post(route('appointments.status', this.appointment.id), {
                status: this.nextStatus,
                reason: this.reason || undefined,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.nextStatus = '';
                    this.reason = '';
                },
            });
        },
        cancelAppointment() {
            if (!window.confirm(`¿Cancelar la cita #${this.appointment.id}? El espacio quedará disponible.`)) return;

            router.delete(route('appointments.destroy', this.appointment.id), {
                data: { reason: this.reason || 'Cancelada desde el detalle de la cita.' },
                preserveScroll: true,
            });
        },
        formatDate(dateString) {
            if (!dateString) return '';
            return new Intl.DateTimeFormat('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(new Date(`${String(dateString).slice(0, 10)}T12:00:00`));
        },
        formatTime(time) {
            return time ? String(time).slice(0, 5) : '';
        },
    },
};
</script>
