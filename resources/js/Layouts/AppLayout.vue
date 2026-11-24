<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
        <AppSidebar
            :sections="sections"
            :collapsed="sidebarCollapsed"
            :mobile-open="mobileOpen"
            @toggle="sidebarCollapsed = !sidebarCollapsed"
            @close="mobileOpen = false"
        />

        <div class="transition-[padding] duration-200" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'">
            <AppNavbar
                :user="user"
                :roles="roles"
                :sections="sections"
                :notifications="notifications"
                @toggle="toggleSidebar"
            />

            <header
                v-if="showHeader"
                class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <FlashMessage />
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppNavbar from '@/Layouts/AppNavbar.vue';
import AppSidebar from '@/Layouts/AppSidebar.vue';
import FlashMessage from '@/Components/FlashMessage.vue';

defineProps({
    showHeader: { type: Boolean, default: true },
});

const page = usePage();
const sidebarCollapsed = ref(false);
const mobileOpen = ref(false);

const user = computed(() => page.props.auth?.user ?? null);
const roles = computed(() => user.value?.roles ?? []);
const notifications = computed(() => page.props.notifications ?? null);
const isAdmin = computed(() => roles.value.includes('admin'));
const isDoctor = computed(() => roles.value.includes('doctor'));

const sections = computed(() => {
    const operation = [
        { name: 'dashboard', label: 'Panel general', icon: 'dashboard' },
        { name: 'appointments.welcome', label: 'Citas', icon: 'calendar' },
        { name: 'appointments.index', label: 'Listado de citas', icon: 'clipboard' },
        { name: 'agenda.index', label: 'Calendario', icon: 'clock' },
        { name: 'sessions.index', label: 'Sesiones médicas', icon: 'clipboard' },
    ];

    const people = [
        { name: 'patients.index', label: 'Pacientes', icon: 'users' },
        { name: 'doctors.index', label: 'Médicos', icon: 'user' },
    ];

    const settings = [
        { name: 'profile.edit', label: 'Configuración', icon: 'settings' },
    ];

    if (isAdmin.value) {
        return [
            { title: 'Operación', links: operation },
            {
                title: 'Gestión clínica',
                links: [
                    ...people,
                    { name: 'specialties.index', label: 'Especialidades', icon: 'heart' },
                    { name: 'drugs.index', label: 'Medicamentos', icon: 'clipboard' },
                    { name: 'bills.index', label: 'Facturación', icon: 'chart' },
                    { name: 'medicalhistories.index', label: 'Historias clínicas', icon: 'clipboard' },
                ],
            },
            { title: 'Cuenta', links: settings },
        ];
    }

    if (isDoctor.value) {
        return [
            { title: 'Mi consulta', links: operation },
            { title: 'Pacientes', links: people },
            { title: 'Cuenta', links: settings },
        ];
    }

    return [{ title: 'Cuenta', links: settings }];
});

const toggleSidebar = () => {
    if (window.matchMedia('(min-width: 1024px)').matches) {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    } else {
        mobileOpen.value = !mobileOpen.value;
    }
};
</script>
