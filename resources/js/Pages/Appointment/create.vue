<template>
    <Head title="Nueva cita" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                Nueva cita
            </h1>
        </template>

        <template #default>
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="doctor-select"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Médico *
                                </label>
                                <select id="doctor-select" v-model="form.doctor_id"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                                    <option disabled value="">Seleccione un médico</option>
                                    <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                                        Dr. {{ doctor.name }} {{ doctor.last_name }}
                                    </option>
                                </select>
                                <InputError :message="$page.props.errors.doctor_id" class="mt-1" />
                            </div>

                            <div>
                                <label for="patient-select"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Paciente *
                                </label>
                                <select id="patient-select" v-model="form.patient_id"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                                    <option disabled value="">Seleccione un paciente</option>
                                    <option v-for="patient in patients" :key="patient.id" :value="patient.id">
                                        {{ patient.name }} {{ patient.last_name }}
                                    </option>
                                </select>
                                <InputError :message="$page.props.errors.patient_id" class="mt-1" />
                            </div>

                            <div>
                                <label for="drug-select"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Medicamento (opcional)
                                </label>
                                <select id="drug-select" v-model="form.drug_id"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                                    <option value="">Sin medicamento</option>
                                    <option v-for="drug in drugs" :key="drug.id" :value="drug.id">
                                        {{ drug.name }}
                                    </option>
                                </select>
                                <InputError :message="$page.props.errors.drug_id" class="mt-1" />
                            </div>

                            <div>
                                <label for="status-select"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Estado *
                                </label>
                                <select id="status-select" v-model="form.status"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-blue-500 focus:border-blue-500 dark:text-white">
                                    <option v-for="status in statuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <InputError :message="$page.props.errors.status" class="mt-1" />
                            </div>

                            <div>
                                <label for="datepicker"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Fecha *
                                </label>
                                <input id="datepicker" type="date" v-model="form.date"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-purple-500 focus:border-purple-500 dark:text-white">
                                <InputError :message="$page.props.errors.date" class="mt-1" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="starttime"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Inicio *
                                    </label>
                                    <input id="starttime" type="time" v-model="form.starttime"
                                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-purple-500 focus:border-purple-500 dark:text-white">
                                </div>
                                <div>
                                    <label for="endtime"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Fin *
                                    </label>
                                    <input id="endtime" type="time" v-model="form.endtime"
                                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-purple-500 focus:border-purple-500 dark:text-white">
                                </div>
                                <InputError :message="$page.props.errors.starttime" class="mt-1 col-span-2" />
                                <InputError :message="$page.props.errors.endtime" class="mt-1 col-span-2" />
                            </div>

                            <div class="md:col-span-2">
                                <label for="notes"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Notas / motivo de la cita *
                                </label>
                                <textarea id="notes" rows="3" v-model="form.title"
                                    class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-purple-500 focus:border-purple-500 dark:text-white"
                                    placeholder="Motivo de la consulta..."></textarea>
                                <InputError :message="$page.props.errors.title" class="mt-1" />
                            </div>
                        </div>

                        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 p-4">
                            <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
                                Disponibilidad: {{ workingDays }}, {{ availability.start_time }} – {{ availability.end_time }}.
                            </p>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                Duración permitida: {{ availability.min_duration_minutes }}–{{ availability.max_duration_minutes }} minutos.
                                No se permiten citas solapadas para el mismo médico.
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('appointments.index')"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            Cancelar
                            </Link>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors">
                                Crear cita
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

export default {
    components: {
        Head,
        AuthenticatedLayout,
        Link,
        InputError,
    },
    props: {
        patients: {
            type: Array,
            default: () => [],
        },
        doctors: {
            type: Array,
            default: () => [],
        },
        drugs: {
            type: Array,
            default: () => [],
        },
        statuses: {
            type: Array,
            default: () => [],
        },
        availability: {
            type: Object,
            default: () => ({
                start_time: '08:00',
                end_time: '18:00',
                weekdays: [1, 2, 3, 4, 5],
                min_duration_minutes: 15,
                max_duration_minutes: 240,
            }),
        },
    },
    data() {
        return {
            form: {
                title: '',
                doctor_id: '',
                patient_id: '',
                drug_id: '',
                status: 'pending',
                date: '',
                starttime: '',
                endtime: '',
                attended: false,
                active: true,
            },
        };
    },
    computed: {
        workingDays() {
            const names = {
                1: 'lunes',
                2: 'martes',
                3: 'miércoles',
                4: 'jueves',
                5: 'viernes',
                6: 'sábado',
                7: 'domingo',
            };
            const days = this.availability.weekdays || [];

            if (days.length === 5 && [1, 2, 3, 4, 5].every((day) => days.includes(day))) {
                return 'lunes a viernes';
            }

            return days.map((day) => names[day] || day).join(', ');
        },
    },
    methods: {
        submit() {
            router.post(route('appointments.store'), this.form, {
                preserveScroll: true,
            });
        },
    },
};
</script>
