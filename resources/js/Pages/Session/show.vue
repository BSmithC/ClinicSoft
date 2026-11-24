<template>
    <Head title="Detalle de sesión" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ session.title }}</h1>
                    <p class="text-sm text-slate-500">{{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }} · {{ session.specialty?.name ?? 'Sin especialidad' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('sessions.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Volver</Link>
                    <Link v-if="!isCancelled" :href="route('sessions.edit', session.id)" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Editar</Link>
                </div>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Configuración</h2>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Médico</dt><dd class="font-semibold text-slate-800 dark:text-white">Dr. {{ session.doctor?.name }} {{ session.doctor?.last_name }}</dd></div>
                        <div><dt class="text-slate-500">Estado</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ statusLabel }}</dd></div>
                        <div><dt class="text-slate-500">Duración por cita</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ session.consultation_minutes }} min</dd></div>
                        <div><dt class="text-slate-500">Capacidad</dt><dd class="font-semibold text-slate-800 dark:text-white">{{ session.appointments?.length ?? 0 }} / {{ session.capacity }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-slate-500">Notas</dt><dd class="text-slate-700 dark:text-slate-200">{{ session.notes || '—' }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-4 text-base font-semibold text-slate-800 dark:text-white">Espacios calculados por el servidor</h2>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div
                            v-for="slot in slots"
                            :key="slot.start"
                            class="rounded-lg border px-2 py-2 text-center text-xs font-medium"
                            :class="slot.available
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200'
                                : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800'"
                        >
                            {{ formatTime(slot.start) }}–{{ formatTime(slot.end) }}
                            <span v-if="!slot.available" class="block text-[10px]">{{ slot.reason }}</span>
                        </div>
                        <p v-if="!slots.length" class="col-span-full text-sm text-slate-500">No hay espacios disponibles para esta sesión.</p>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="border-b border-slate-100 px-5 py-4 text-base font-semibold text-slate-800 dark:border-slate-800 dark:text-white">Citas asignadas</h2>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="appointment in session.appointments" :key="appointment.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div>
                                <Link :href="route('appointments.show', appointment.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">Cita #{{ appointment.id }}</Link>
                                <p class="text-xs text-slate-500">{{ formatTime(appointment.starttime) }} · {{ appointment.patient?.name }} {{ appointment.patient?.last_name }}</p>
                            </div>
                            <StatusBadge :status="appointment.status" />
                        </li>
                        <li v-if="!session.appointments?.length" class="px-5 py-8 text-center text-sm text-slate-500">Aún no hay citas en esta sesión.</li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="mb-3 text-base font-semibold text-slate-800 dark:text-white">Descansos</h2>
                    <ul class="space-y-2 text-sm">
                        <li v-for="breakItem in session.breaks" :key="breakItem.id" class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 p-2 dark:bg-slate-800">
                            <span class="text-slate-600 dark:text-slate-300">{{ formatTime(breakItem.start_time) }}–{{ formatTime(breakItem.end_time) }}<br />{{ breakItem.reason || 'Sin motivo' }}</span>
                            <button type="button" class="text-xs font-semibold text-rose-600 hover:underline" @click="removeBreak(breakItem)">Quitar</button>
                        </li>
                        <li v-if="!session.breaks?.length" class="text-slate-500">Sin descansos registrados.</li>
                    </ul>
                    <form v-if="!isCancelled" @submit.prevent="addBreak" class="mt-4 space-y-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <input v-model="breakForm.start_time" type="time" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <input v-model="breakForm.end_time" type="time" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <input v-model="breakForm.reason" type="text" placeholder="Motivo" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <button type="submit" class="w-full rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white dark:bg-slate-700">Agregar descanso</button>
                    </form>
                </section>

                <section v-if="!isCancelled" class="rounded-xl border border-rose-200 bg-white p-5 shadow-sm dark:border-rose-900 dark:bg-slate-900">
                    <h2 class="mb-3 text-base font-semibold text-rose-700 dark:text-rose-300">Cancelar sesión</h2>
                    <p class="mb-3 text-xs text-slate-500">Las citas pendientes o confirmadas quedarán canceladas y su historial quedará registrado.</p>
                    <form @submit.prevent="cancelSession" class="space-y-2">
                        <input v-model="cancelReason" type="text" placeholder="Motivo de la cancelación" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
                        <button type="submit" class="w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Cancelar sesión</button>
                    </form>
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
        session: {
            type: Object,
            required: true,
        },
        slots: {
            type: Array,
            default: () => [],
        },
        statuses: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            cancelReason: '',
            breakForm: {
                start_time: '',
                end_time: '',
                reason: '',
            },
        };
    },
    computed: {
        statusValue() {
            const status = this.session.status;
            return status && typeof status === 'object' ? status.value : status;
        },
        statusLabel() {
            return this.statuses.find((item) => item.value === this.statusValue)?.label ?? this.statusValue ?? '—';
        },
        isCancelled() {
            return this.statusValue === 'cancelled';
        },
    },
    methods: {
        cancelSession() {
            if (!window.confirm(`¿Cancelar la sesión "${this.session.title}"? Esta acción no se puede deshacer.`)) return;

            router.post(route('sessions.cancel', this.session.id), {
                reason: this.cancelReason || undefined,
            }, { preserveScroll: true });
        },
        addBreak() {
            router.post(route('sessionBreaks.store', this.session.id), this.breakForm, {
                preserveScroll: true,
                onSuccess: () => {
                    this.breakForm.start_time = '';
                    this.breakForm.end_time = '';
                    this.breakForm.reason = '';
                },
            });
        },
        removeBreak(breakItem) {
            if (!window.confirm('¿Quitar este descanso de la sesión?')) return;
            router.delete(route('sessionBreaks.destroy', breakItem.id), { preserveScroll: true });
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
