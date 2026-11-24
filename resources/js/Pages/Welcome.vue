<template>
    <Head title="Gestión Clínica" />

    <div class="min-h-screen bg-white dark:bg-slate-950">
        <nav class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white shadow-sm">
                        <UiIcon name="heart" size="h-5 w-5" />
                    </span>
                    <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">Gestión Clínica</span>
                </Link>

                <div v-if="canLogin" class="flex items-center">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="ui-btn-primary px-3 py-2 text-xs sm:px-4 sm:text-sm"
                    >
                        Ir al panel
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="ui-btn-secondary px-3 py-2 text-xs sm:px-4 sm:text-sm"
                    >
                        Iniciar sesión
                    </Link>
                </div>
            </div>
        </nav>

        <header class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20">
                        <UiIcon name="heart" size="h-3.5 w-3.5" />
                        Plataforma médica
                    </span>
                    <h1 class="mt-5 text-4xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                        Gestión clínica <span class="text-brand-600 dark:text-brand-400">integral</span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-500 dark:text-slate-400 sm:text-lg">
                        Pacientes, agenda, historiales y facturación en un solo lugar, con la información real de su consultorio.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link href="#funciones" class="ui-btn-primary">
                            Conocer funciones
                            <UiIcon name="chevron-right" size="h-4 w-4" />
                        </Link>
                        <Link
                            v-if="canLogin"
                            :href="$page.props.auth.user ? route('dashboard') : route('login')"
                            class="ui-btn-secondary"
                        >
                            {{ $page.props.auth.user ? 'Ir al panel' : 'Iniciar sesión' }}
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section id="funciones" class="py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">Funcionalidades</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                            Todo lo que necesita en un solo lugar
                        </h2>
                        <p class="mt-4 text-base leading-7 text-slate-500 dark:text-slate-400">
                            Herramientas pensadas para el flujo diario del personal clínico autorizado.
                        </p>
                    </div>

                    <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <article v-for="feature in features" :key="feature.title" class="ui-card p-6">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                <UiIcon :name="feature.icon" size="h-5 w-5" />
                            </span>
                            <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">{{ feature.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ feature.description }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section v-if="canLogin && !$page.props.auth.user" class="bg-brand-700">
                <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 sm:py-20 lg:px-8">
                    <h2 class="text-3xl font-bold tracking-tight text-white">
                        Acceda a su consultorio
                    </h2>
                    <p class="mt-4 text-base leading-7 text-brand-100">
                        Ingrese con su usuario para ver su agenda, pacientes y registros clínicos.
                    </p>
                    <Link :href="route('login')" class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-700">
                        Iniciar sesión
                        <UiIcon name="chevron-right" size="h-4 w-4" />
                    </Link>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p class="text-center text-sm text-slate-400 dark:text-slate-500">
                        &copy; {{ currentYear }} Gestión Clínica. Todos los derechos reservados.
                    </p>
                    <p class="text-center text-xs text-slate-400 dark:text-slate-500">
                        Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'Welcome',
    components: { Head, Link, UiIcon },
    props: {
        canLogin: { type: Boolean, default: false },
        canRegister: { type: Boolean, default: false },
        laravelVersion: { type: String, required: true },
        phpVersion: { type: String, required: true },
    },
    data() {
        return {
            features: [
                {
                    title: 'Pacientes',
                    icon: 'users',
                    description: 'Fichas con datos de contacto, demografía y acceso a su historia clínica.',
                },
                {
                    title: 'Citas médicas',
                    icon: 'calendar',
                    description: 'Programación por sesión, estados y transiciones registradas en el historial.',
                },
                {
                    title: 'Historias médicas',
                    icon: 'clipboard',
                    description: 'Diagnósticos, síntomas, constantes y evolución de cada consulta.',
                },
                {
                    title: 'Medicamentos y facturación',
                    icon: 'heart',
                    description: 'Catálogo de medicamentos y facturas con subtotal, ITBIS y total calculados.',
                },
                {
                    title: 'Disponibilidad real',
                    icon: 'clock',
                    description: 'Cupos por sesión, descansos, fechas bloqueadas y validación de citas.',
                },
                {
                    title: 'Acceso por roles',
                    icon: 'settings',
                    description: 'Permisos diferenciados para administración y personal médico.',
                },
            ],
        };
    },
    computed: {
        currentYear() {
            return new Date().getFullYear();
        },
    },
};
</script>
