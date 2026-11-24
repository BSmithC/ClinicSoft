<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-slate-200 bg-white transition-[width] duration-200 dark:border-slate-800 dark:bg-slate-900 lg:flex"
        :class="collapsed ? 'w-20' : 'w-64'"
    >
        <div class="flex h-16 shrink-0 items-center border-b border-slate-200 dark:border-slate-800" :class="collapsed ? 'justify-center px-3' : 'gap-3 px-5'">
            <Link :href="route('dashboard')" class="flex items-center gap-3" :aria-label="collapsed ? 'Panel general' : null">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                    <UiIcon name="heart" size="h-5 w-5" />
                </span>
                <span v-if="!collapsed" class="min-w-0">
                    <span class="block truncate text-sm font-bold tracking-tight text-slate-900 dark:text-white">Gestor de Citas</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-widest text-brand-600 dark:text-brand-400">Software clínico</span>
                </span>
            </Link>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Navegación principal">
            <div v-for="section in sections" :key="section.title" class="mb-6 last:mb-0">
                <p v-if="!collapsed" class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                    {{ section.title }}
                </p>
                <div v-else class="mx-3 mb-3 border-t border-slate-100 dark:border-slate-800" />

                <Link
                    v-for="link in section.links"
                    :key="link.name"
                    :href="route(link.name)"
                    class="group relative mb-1 flex items-center rounded-lg text-sm font-medium transition-colors"
                    :class="[
                        isActive(link.name)
                            ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
                        collapsed ? 'justify-center px-3 py-2.5' : 'gap-3 px-3 py-2.5',
                    ]"
                    :title="collapsed ? link.label : null"
                >
                    <UiIcon :name="link.icon" size="h-5 w-5" class="shrink-0" />
                    <span v-if="!collapsed" class="truncate">{{ link.label }}</span>
                    <span
                        v-if="isActive(link.name)"
                        class="absolute inset-y-2 start-0 w-1 rounded-r-full bg-brand-600 dark:bg-brand-400"
                        aria-hidden="true"
                    />
                </Link>
            </div>
        </nav>

        <div class="shrink-0 border-t border-slate-200 p-3 dark:border-slate-800">
            <button
                type="button"
                class="ui-btn-ghost w-full"
                :class="collapsed ? 'justify-center' : 'justify-start'"
                :aria-label="collapsed ? 'Expandir menú' : 'Contraer menú'"
                @click="emit('toggle')"
            >
                <UiIcon :name="collapsed ? 'chevron-right' : 'chevron-left'" size="h-4 w-4" />
                <span v-if="!collapsed">Contraer menú</span>
            </button>
        </div>
    </aside>

    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden" @click="emit('close')" />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-200 ease-out"
            enter-from-class="-translate-x-full"
            leave-active-class="transition-transform duration-150 ease-in"
            leave-to-class="-translate-x-full"
        >
            <aside
                v-if="mobileOpen"
                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-white shadow-2xl dark:bg-slate-900 lg:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Navegación principal"
            >
                <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-200 px-4 dark:border-slate-800">
                    <Link :href="route('dashboard')" class="flex items-center gap-3" @click="emit('close')">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                            <UiIcon name="heart" size="h-5 w-5" />
                        </span>
                        <span class="text-sm font-bold text-slate-900 dark:text-white">Gestor de Citas</span>
                    </Link>
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Cerrar menú" @click="emit('close')">
                        <UiIcon name="close" size="h-5 w-5" />
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Navegación móvil">
                    <div v-for="section in sections" :key="section.title" class="mb-5">
                        <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                            {{ section.title }}
                        </p>
                        <Link
                            v-for="link in section.links"
                            :key="link.name"
                            :href="route(link.name)"
                            class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                            :class="isActive(link.name)
                                ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                            @click="emit('close')"
                        >
                            <UiIcon :name="link.icon" size="h-5 w-5" class="shrink-0" />
                            <span class="truncate">{{ link.label }}</span>
                        </Link>
                    </div>
                </nav>

                <div class="shrink-0 border-t border-slate-200 p-3 dark:border-slate-800">
                    <Link :href="route('logout')" method="post" as="button" class="ui-btn-ghost w-full justify-start text-rose-600 dark:text-rose-400">
                        <UiIcon name="logout" size="h-4 w-4" />
                        Cerrar sesión
                    </Link>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import UiIcon from '@/Components/UiIcon.vue';

defineProps({
    sections: { type: Array, default: () => [] },
    collapsed: { type: Boolean, default: false },
    mobileOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'close']);

const isActive = (name) => route().current(name) || route().current(`${name}.*`);
</script>
