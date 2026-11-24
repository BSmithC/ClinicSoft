<template>
    <Head title="Sesiones médicas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Sesiones médicas</h1>
                    <p class="text-sm text-slate-500">Bloques de atención con cupos, descansos y validación de solapamientos.</p>
                </div>
                <Link :href="route('sessions.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nueva sesión</Link>
            </div>
        </template>

        <form @submit.prevent="submit" class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <label for="date" class="mb-1 block text-xs font-medium text-slate-500">Fecha</label>
                <input id="date" v-model="form.date" type="date" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" />
            </div>
            <div>
                <label for="specialty_id" class="mb-1 block text-xs font-medium text-slate-500">Especialidad</label>
                <select id="specialty_id" v-model="form.specialty_id" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Todas</option>
                    <option v-for="specialty in specialties" :key="specialty.id" :value="specialty.id">{{ specialty.name }}</option>
                </select>
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-medium text-slate-500">Estado</label>
                <select id="status" v-model="form.status" class="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Todos</option>
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white dark:bg-slate-700">Filtrar</button>
        </form>

        <section v-if="sessions.data.length" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Sesión</th>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3">Fecha y horario</th>
                            <th class="px-4 py-3">Cupos</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="session in sessions.data" :key="session.id">
                            <td class="px-4 py-3">
                                <Link :href="route('sessions.show', session.id)" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ session.title }}</Link>
                                <p class="text-xs text-slate-500">{{ session.specialty?.name ?? 'Sin especialidad' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">Dr. {{ session.doctor?.name }} {{ session.doctor?.last_name }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ formatDate(session.date) }} · {{ formatTime(session.start_time) }}–{{ formatTime(session.end_time) }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ session.appointments_count }} / {{ session.capacity }} · {{ session.consultation_minutes }} min</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ statusLabel(session.status) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-3 text-xs font-semibold">
                                    <Link :href="route('sessions.show', session.id)" class="text-sky-600 hover:underline">Ver</Link>
                                    <Link v-if="!isCancelled(session)" :href="route('sessions.edit', session.id)" class="text-amber-600 hover:underline">Editar</Link>
                                    <button v-if="!isCancelled(session)" type="button" class="text-rose-600 hover:underline" @click="cancel(session)">Cancelar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :pagination="sessions" :filters="activeFilters" />
        </section>

        <section v-else class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="font-medium text-slate-700 dark:text-slate-200">No hay sesiones para los filtros seleccionados.</p>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        Pagination,
    },
    props: {
        sessions: {
            type: Object,
            required: true,
        },
        specialties: {
            type: Array,
            default: () => [],
        },
        statuses: {
            type: Array,
            default: () => [],
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            form: {
                date: this.filters.date ?? '',
                specialty_id: this.filters.specialty_id ?? '',
                status: this.filters.status ?? '',
            },
        };
    },
    computed: {
        activeFilters() {
            return Object.fromEntries(Object.entries(this.form).filter(([, value]) => value !== ''));
        },
    },
    methods: {
        submit() {
            router.get(route('sessions.index'), this.form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        isCancelled(session) {
            return this.statusValue(session) === 'cancelled';
        },
        statusValue(session) {
            const status = session.status;
            return status && typeof status === 'object' ? status.value : status;
        },
        statusLabel(status) {
            const value = status && typeof status === 'object' ? status.value : status;
            return this.statuses.find((item) => item.value === value)?.label ?? value ?? '—';
        },
        cancel(session) {
            if (!window.confirm(`¿Cancelar la sesión "${session.title}"? Las citas afectadas quedarán canceladas y registradas.`)) return;

            router.post(route('sessions.cancel', session.id), {
                reason: 'Cancelada desde el listado de sesiones.',
            }, { preserveScroll: true });
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
