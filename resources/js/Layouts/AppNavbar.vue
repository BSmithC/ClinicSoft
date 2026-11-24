<template>
    <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 sm:px-6">
        <button
            type="button"
            class="ui-btn-ghost shrink-0 px-2"
            aria-label="Alternar menú lateral"
            @click="emit('toggle')"
        >
            <UiIcon name="menu" size="h-5 w-5" />
        </button>

        <div class="hidden min-w-0 md:block">
            <nav class="flex items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500" aria-label="Ruta de navegación">
                <span>Inicio</span>
                <UiIcon v-if="activeLink" name="chevron-right" size="h-3 w-3" />
                <span v-if="activeLink" class="truncate">{{ activeLink.section }}</span>
                <UiIcon v-if="activeLink" name="chevron-right" size="h-3 w-3" />
                <span class="truncate font-medium text-slate-600 dark:text-slate-300">{{ pageTitle }}</span>
            </nav>
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ pageTitle }}</p>
        </div>

        <form
            v-if="searchContext"
            class="ms-auto hidden w-full max-w-xs lg:block"
            @submit.prevent="submitSearch"
        >
            <SearchInput
                v-model="searchTerm"
                :placeholder="`Buscar ${searchContext.label}...`"
                input-id="navbar-search"
                @search="submitSearch"
            />
        </form>

        <div class="flex shrink-0 items-center gap-1" :class="searchContext ? 'lg:ms-0 ms-auto' : 'ms-auto'">
            <button
                type="button"
                class="ui-btn-ghost px-2.5"
                :aria-label="isDark ? 'Activar modo claro' : 'Activar modo oscuro'"
                :title="isDark ? 'Modo claro' : 'Modo oscuro'"
                @click="toggleTheme"
            >
                <UiIcon :name="isDark ? 'sun' : 'moon'" size="h-5 w-5" />
            </button>

            <div class="relative">
                <button
                    type="button"
                    class="ui-btn-ghost relative px-2.5"
                    aria-label="Notificaciones"
                    :aria-expanded="notificationsOpen"
                    @click="notificationsOpen = !notificationsOpen; userOpen = false"
                >
                    <UiIcon name="bell" size="h-5 w-5" />
                    <span
                        v-if="pendingCount > 0"
                        class="absolute end-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                    >
                        {{ pendingCount > 9 ? '9+' : pendingCount }}
                    </span>
                </button>

                <div v-if="notificationsOpen" class="fixed inset-0 z-30" @click="notificationsOpen = false" />
                <div
                    v-if="notificationsOpen"
                    class="absolute end-0 z-40 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <p class="px-3 py-2 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Notificaciones
                    </p>
                    <Link
                        :href="route('appointments.index')"
                        class="flex items-start gap-3 rounded-lg px-3 py-2.5 transition hover:bg-slate-50 dark:hover:bg-slate-800"
                        @click="notificationsOpen = false"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-300">
                            <UiIcon name="clock" size="h-4 w-4" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-slate-800 dark:text-white">
                                {{ pendingCount }} {{ pendingCount === 1 ? 'cita pendiente' : 'citas pendientes' }}
                            </span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Requieren confirmación</span>
                        </span>
                    </Link>
                    <Link
                        :href="route('agenda.index')"
                        class="flex items-start gap-3 rounded-lg px-3 py-2.5 transition hover:bg-slate-50 dark:hover:bg-slate-800"
                        @click="notificationsOpen = false"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            <UiIcon name="calendar" size="h-4 w-4" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-slate-800 dark:text-white">
                                {{ todayCount }} {{ todayCount === 1 ? 'cita hoy' : 'citas hoy' }}
                            </span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Agenda del día</span>
                        </span>
                    </Link>
                </div>
            </div>

            <div class="relative">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg p-1.5 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                    :aria-expanded="userOpen"
                    aria-label="Menú de usuario"
                    @click="userOpen = !userOpen; notificationsOpen = false"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <UiIcon name="user" size="h-4 w-4" />
                    </span>
                    <span class="hidden text-start sm:block">
                        <span class="block max-w-32 truncate text-sm font-semibold text-slate-800 dark:text-white">{{ user?.name }}</span>
                        <span class="block max-w-32 truncate text-xs text-slate-500 dark:text-slate-400">{{ roleLabel }}</span>
                    </span>
                    <UiIcon name="chevron-down" size="h-4 w-4" class="hidden text-slate-400 sm:block" />
                </button>

                <div v-if="userOpen" class="fixed inset-0 z-30" @click="userOpen = false" />
                <div
                    v-if="userOpen"
                    class="absolute end-0 z-40 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="border-b border-slate-100 px-3 py-2.5 dark:border-slate-800 sm:hidden">
                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">{{ user?.name }}</p>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ roleLabel }}</p>
                    </div>
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="userOpen = false"
                    >
                        <UiIcon name="settings" size="h-4 w-4" />
                        Configuración de cuenta
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-start text-sm text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"
                        @click="userOpen = false"
                    >
                        <UiIcon name="logout" size="h-4 w-4" />
                        Cerrar sesión
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SearchInput from '@/Components/SearchInput.vue';
import UiIcon from '@/Components/UiIcon.vue';

const props = defineProps({
    user: { type: Object, default: null },
    roles: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    notifications: { type: Object, default: null },
});

const emit = defineEmits(['toggle']);

const notificationsOpen = ref(false);
const userOpen = ref(false);
const searchTerm = ref('');
const isDark = ref(document.documentElement.classList.contains('dark'));

const searchableModules = [
    { prefix: 'patients', name: 'patients.index', label: 'pacientes' },
    { prefix: 'doctors', name: 'doctors.index', label: 'médicos' },
    { prefix: 'appointments', name: 'appointments.index', label: 'citas' },
    { prefix: 'specialties', name: 'specialties.index', label: 'especialidades' },
    { prefix: 'drugs', name: 'drugs.index', label: 'medicamentos' },
    { prefix: 'bills', name: 'bills.index', label: 'facturación' },
];

const currentRoute = computed(() => route().current() ?? '');

const activeLink = computed(() => {
    for (const section of props.sections) {
        const link = section.links.find(
            (item) => currentRoute.value === item.name || currentRoute.value.startsWith(`${item.name}.`)
        );
        if (link) {
            return { section: section.title, label: link.label };
        }
    }
    return null;
});

const pageTitle = computed(() => {
    if (activeLink.value) return activeLink.value.label;
    if (currentRoute.value === 'profile.edit') return 'Configuración de cuenta';
    return 'Panel general';
});

const searchContext = computed(() =>
    searchableModules.find((module) =>
        currentRoute.value === module.prefix || currentRoute.value.startsWith(`${module.prefix}.`)
    ) ?? null
);

const roleLabel = computed(() => {
    const labels = { admin: 'Administrador', doctor: 'Médico', patient: 'Paciente' };
    if (!props.roles.length) return 'Usuario';
    return props.roles.map((role) => labels[role] ?? role).join(', ');
});

const pendingCount = computed(() => Number(props.notifications?.pending ?? 0));
const todayCount = computed(() => Number(props.notifications?.today ?? 0));

const toggleTheme = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    document.documentElement.style.colorScheme = isDark.value ? 'dark' : 'light';
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark: isDark.value } }));
};

const submitSearch = () => {
    const term = searchTerm.value.trim();
    if (!searchContext.value || !term) return;
    router.get(
        route(searchContext.value.name),
        { search: term },
        { preserveState: true, preserveScroll: true }
    );
};
</script>
