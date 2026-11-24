<template>
    <Head title="Detalle de cita" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    Cita #{{ appointment.id }}
                </h1>
                <span :class="statusClass"
                    class="px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap">
                    {{ statusLabel }}
                </span>
            </div>
        </template>

        <template #default>
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 space-y-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 shadow-sm">
                            <h3 class="font-semibold text-lg text-gray-700 dark:text-white mb-3">
                                Información de la cita
                            </h3>
                            <dl class="space-y-3">
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Paciente</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ appointment.patient?.name }} {{ appointment.patient?.last_name }}
                                    </dd>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Médico</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                        Dr. {{ appointment.doctor?.name }} {{ appointment.doctor?.last_name }}
                                    </dd>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ formatDate(appointment.date) }}
                                    </dd>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Horario</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ formatTime(appointment.starttime) }} - {{ formatTime(appointment.endtime) }}
                                    </dd>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Medicamento</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ appointment.drug?.name || 'No especificado' }}
                                    </dd>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Asistencia</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ appointment.attended ? 'Sí' : 'No' }}
                                    </dd>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between pt-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notas</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-white sm:text-right max-w-sm">
                                        {{ appointment.title || 'Sin notas' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <Link :href="route('appointments.index')"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Volver
                        </Link>
                        <Link :href="route('appointments.edit', appointment.id)"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Editar
                        </Link>
                        <button v-if="statusValue !== 'cancelled'" type="button" @click="cancelAppointment"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Cancelar cita
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        Link,
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
    },
    computed: {
        statusValue() {
            const status = this.appointment.status;

            return status && typeof status === 'object' ? status.value : status || 'pending';
        },
        statusLabel() {
            const status = this.statuses.find((item) => item.value === this.statusValue);

            return status ? status.label : this.statusValue;
        },
        statusClass() {
            return {
                'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200': this.statusValue === 'pending',
                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': this.statusValue === 'confirmed',
                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': this.statusValue === 'completed',
                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': this.statusValue === 'cancelled',
            };
        },
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) {
                return '';
            }

            const date = new Date(`${dateString}T00:00:00`);
            if (Number.isNaN(date.getTime())) {
                return dateString;
            }

            return new Intl.DateTimeFormat('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(date);
        },
        formatTime(time) {
            return time ? String(time).substring(0, 5) : '';
        },
        cancelAppointment() {
            if (!window.confirm(`¿Cancelar la cita #${this.appointment.id}? La cita no se eliminará.`)) {
                return;
            }

            router.delete(route('appointments.destroy', this.appointment.id), {
                preserveScroll: true,
            });
        },
    },
};
</script>
