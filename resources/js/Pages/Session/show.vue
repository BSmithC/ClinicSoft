<template>
    <Head :title="session.title" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Agenda clínica"
                :title="session.title"
                :description="`${formatDate(session.date)} · ${formatTime(session.start_time)}–${formatTime(session.end_time)} · ${session.specialty?.name ?? 'Sin especialidad'}`"
            >
                <template #actions>
                    <StatusBadge :status="session.status" />
                    <Link :href="route('sessions.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver
                    </Link>
                    <Link v-if="!isCancelled" :href="route('sessions.edit', session.id)" class="ui-btn-primary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="ui-card p-6">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Configuración</h2>
                    <dl class="grid gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Médico</dt>
                            <dd class="mt-1 font-medium text-slate-800 dark:text-white">Dr. {{ fullName(session.doctor) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Duración por cita</dt>
                            <dd class="mt-1 font-medium text-slate-800 dark:text-white">{{ session.consultation_minutes }} min</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Capacidad</dt>
                            <dd class="mt-1 font-medium text-slate-800 dark:text-white">{{ session.appointments?.length ?? 0 }} / {{ session.capacity }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Notas</dt>
                            <dd class="mt-1 font-medium text-slate-800 dark:text-white">{{ session.notes || '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="ui-card p-6">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Espacios calculados por el servidor</h2>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div
                            v-for="slot in slots"
                            :key="slot.start"
                            class="rounded-lg border px-2 py-2 text-center text-xs font-medium"
                            :class="slot.available
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800'"
                        >
                            {{ formatTime(slot.start) }}–{{ formatTime(slot.end) }}
                            <span v-if="!slot.available" class="block text-[10px]">{{ slot.reason }}</span>
                        </div>
                        <p v-if="!slots.length" class="col-span-full text-sm text-slate-500 dark:text-slate-400">
                            No hay espacios disponibles para esta sesión.
                        </p>
                    </div>
                </section>

                <section class="ui-card overflow-hidden">
                    <h2 class="border-b border-slate-200 px-6 py-4 text-sm font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        Citas asignadas
                    </h2>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="appointment in session.appointments" :key="appointment.id" class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                            <div>
                                <Link :href="route('appointments.show', appointment.id)" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                    Cita #{{ appointment.id }}
                                </Link>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatTime(appointment.starttime) }} · {{ fullName(appointment.patient) }}
                                </p>
                            </div>
                            <StatusBadge :status="appointment.status" />
                        </li>
                        <li v-if="!session.appointments?.length" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            Aún no hay citas en esta sesión.
                        </li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="ui-card p-5">
                    <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Descansos</h2>
                    <ul class="space-y-2 text-sm">
                        <li v-for="breakItem in session.breaks" :key="breakItem.id" class="flex items-start justify-between gap-2 rounded-lg bg-slate-50 p-2 dark:bg-slate-800/60">
                            <span class="text-slate-600 dark:text-slate-300">
                                {{ formatTime(breakItem.start_time) }}–{{ formatTime(breakItem.end_time) }}<br />
                                {{ breakItem.reason || 'Sin motivo' }}
                            </span>
                            <button type="button" class="shrink-0 text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400" @click="askRemoveBreak(breakItem)">
                                Quitar
                            </button>
                        </li>
                        <li v-if="!session.breaks?.length" class="text-slate-500 dark:text-slate-400">Sin descansos registrados.</li>
                    </ul>
                    <form v-if="!isCancelled" @submit.prevent="addBreak" class="mt-4 space-y-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                        <FormField label="Inicio" name="break-start">
                            <input v-model="breakForm.start_time" type="time" class="ui-input" />
                        </FormField>
                        <FormField label="Fin" name="break-end">
                            <input v-model="breakForm.end_time" type="time" class="ui-input" />
                        </FormField>
                        <FormField label="Motivo" name="break-reason">
                            <input v-model="breakForm.reason" type="text" class="ui-input" placeholder="Motivo" />
                        </FormField>
                        <button type="submit" class="ui-btn-secondary w-full">
                            <UiIcon name="plus" size="h-4 w-4" />
                            Agregar descanso
                        </button>
                    </form>
                </section>

                <section v-if="!isCancelled" class="ui-card border-rose-200 p-5 dark:border-rose-900">
                    <h2 class="mb-2 text-sm font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">Cancelar sesión</h2>
                    <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
                        Las citas pendientes o confirmadas quedarán canceladas y su historial quedará registrado.
                    </p>
                    <FormField label="Motivo de la cancelación" name="cancel-reason">
                        <input v-model="cancelReason" type="text" class="ui-input" />
                    </FormField>
                    <button type="button" class="ui-btn-secondary mt-3 w-full border-rose-200 text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-500/10" @click="confirmCancel = true">
                        <UiIcon name="close" size="h-4 w-4" />
                        Cancelar sesión
                    </button>
                </section>
            </aside>
        </div>

        <ConfirmDialog
            :open="confirmCancel"
            title="Cancelar sesión"
            :message="`¿Cancelar la sesión '${session.title}'? Esta acción no se puede deshacer.`"
            confirm-label="Cancelar sesión"
            tone="danger"
            @cancel="confirmCancel = false"
            @confirm="cancelSession"
        />
        <ConfirmDialog
            :open="Boolean(breakToRemove)"
            title="Quitar descanso"
            message="¿Quitar este descanso de la sesión?"
            confirm-label="Quitar descanso"
            tone="danger"
            @cancel="breakToRemove = null"
            @confirm="removeBreak"
        />
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FormField from '@/Components/FormField.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        FormField,
        StatusBadge,
        ConfirmDialog,
        UiIcon,
    },
    props: {
        session: { type: Object, required: true },
        slots: { type: Array, default: () => [] },
        statuses: { type: Array, default: () => [] },
    },
    data() {
        return {
            cancelReason: '',
            confirmCancel: false,
            breakToRemove: null,
            breakForm: { start_time: '', end_time: '', reason: '' },
        };
    },
    computed: {
        statusValue() {
            const status = this.session.status;
            return status && typeof status === 'object' ? status.value : status;
        },
        isCancelled() {
            return this.statusValue === 'cancelled';
        },
    },
    methods: {
        cancelSession() {
            this.confirmCancel = false;
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
        askRemoveBreak(breakItem) {
            this.breakToRemove = breakItem;
        },
        removeBreak() {
            const breakItem = this.breakToRemove;
            this.breakToRemove = null;
            if (!breakItem) return;
            router.delete(route('sessionBreaks.destroy', breakItem.id), { preserveScroll: true });
        },
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
