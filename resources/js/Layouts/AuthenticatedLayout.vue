<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();
const sidebarOpen = ref(false);
const roles = computed(() => page.props.auth?.user?.roles ?? []);
const isAdmin = computed(() => roles.value.includes('admin'));
const isDoctor = computed(() => roles.value.includes('doctor'));

const sections = computed(() => {
    const common = [
        { name: 'dashboard', label: 'Panel general', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
        { name: 'appointments.index', label: 'Citas', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
        { name: 'agenda.index', label: 'Agenda', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
        { name: 'sessions.index', label: 'Sesiones médicas', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    ];

    const clinical = [
        { name: 'patients.index', label: 'Pacientes', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
        { name: 'doctors.index', label: 'Médicos', icon: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    ];

    if (isAdmin.value) {
        return [
            { title: 'Operación', links: common },
            { title: 'Administración clínica', links: [...clinical,
                { name: 'specialties.index', label: 'Especialidades', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
                { name: 'drugs.index', label: 'Medicamentos', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' },
                { name: 'bills.index', label: 'Facturación', icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z' },
                { name: 'medicalhistories.index', label: 'Historias clínicas', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            ] },
        ];
    }

    if (isDoctor.value) {
        return [
            { title: 'Mi consulta', links: common },
            { title: 'Pacientes', links: clinical },
        ];
    }

    return [];
});

const isCurrent = (name) => route().current(name);
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950">
        <!-- Sidebar desktop -->
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 lg:flex">
            <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-800">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <ApplicationLogo class="h-8 w-8 fill-current text-blue-600 dark:text-blue-400" />
                    <span class="text-sm font-bold tracking-tight text-slate-800 dark:text-white">Gestor de Citas</span>
                </Link>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                <div v-for="section in sections" :key="section.title">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ section.title }}</p>
                    <Link
                        v-for="link in section.links"
                        :key="link.name"
                        :href="route(link.name)"
                        class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                        :class="isCurrent(link.name)
                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" :d="link.icon" />
                        </svg>
                        {{ link.label }}
                    </Link>
                </div>
            </nav>

            <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">{{ $page.props.auth.user.name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ roles.join(', ') }}</p>
                    </div>
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button type="button" class="rounded-full bg-slate-200 p-1 text-slate-600 dark:bg-slate-700 dark:text-slate-200" aria-label="Opciones de usuario">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Cerrar sesión</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </aside>

        <!-- Topbar / mobile navigation -->
        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden"
                    aria-label="Abrir menú"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <Link :href="route('dashboard')" class="text-sm font-bold text-slate-800 dark:text-white lg:hidden">Gestor de Citas</Link>
                <div class="ms-auto flex items-center gap-3">
                    <span class="hidden text-xs text-slate-500 sm:inline">{{ $page.props.auth.user.email }}</span>
                    <Link :href="route('profile.edit')" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">Perfil</Link>
                </div>
            </header>

            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
                @click="sidebarOpen = false"
            >
                <aside class="h-full w-72 overflow-y-auto bg-white p-4 dark:bg-slate-900" @click.stop>
                    <div v-for="section in sections" :key="section.title" class="mb-5">
                        <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ section.title }}</p>
                        <Link
                            v-for="link in section.links"
                            :key="link.name"
                            :href="route(link.name)"
                            class="block rounded-lg px-3 py-2 text-sm font-medium"
                            :class="isCurrent(link.name) ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200' : 'text-slate-600 dark:text-slate-300'"
                            @click="sidebarOpen = false"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                    <Link :href="route('logout')" method="post" as="button" class="block w-full rounded-lg bg-slate-100 px-3 py-2 text-left text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        Cerrar sesión
                    </Link>
                </aside>
            </div>

            <header v-if="$slots.header" class="border-b border-slate-200 bg-white px-4 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <slot name="header" />
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>

        <FlashMessage />
    </div>
</template>
