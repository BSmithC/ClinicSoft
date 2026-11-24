<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-50 px-4 py-10 dark:bg-slate-950">
        <div class="pointer-events-none absolute inset-0 opacity-60 dark:opacity-30" aria-hidden="true">
            <svg class="h-full w-full text-brand-200 dark:text-brand-900" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="guest-pattern" width="28" height="28" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1.2" fill="currentColor" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#guest-pattern)" />
            </svg>
        </div>

        <div class="relative w-full max-w-md">
            <Link href="/" class="mx-auto mb-6 flex w-fit items-center gap-3 rounded-xl px-2 py-1 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                    <UiIcon name="heart" size="h-6 w-6" />
                </span>
                <span>
                    <span class="block text-lg font-bold tracking-tight text-slate-900 dark:text-white">Gestión Clínica</span>
                    <span class="block text-xs font-medium text-slate-500 dark:text-slate-400">Plataforma médica</span>
                </span>
            </Link>

            <div class="ui-card p-6 sm:p-8">
                <slot />
            </div>

            <p class="mt-5 text-center text-xs text-slate-400 dark:text-slate-500">
                Acceso restringido al personal autorizado.
            </p>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'GuestLayout',
    components: { Link, UiIcon },
};
</script>
