<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="mb-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                <UiIcon name="heart" size="h-6 w-6" />
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Bienvenido de nuevo</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Ingrese sus credenciales para acceder a la plataforma clínica.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-5 flex items-start gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20"
            role="status"
        >
            <UiIcon name="check" size="h-4 w-4" class="mt-0.5 shrink-0" />
            <span>{{ status }}</span>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Correo electrónico" name="email" required :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="ui-input"
                    placeholder="nombre@correo.com"
                    required
                    autofocus
                    autocomplete="username"
                />
            </FormField>

            <FormField label="Contraseña" name="password" required :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="ui-input"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                />
            </FormField>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label for="remember" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-900"
                    />
                    Recordarme
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400"
                >
                    ¿Olvidó su contraseña?
                </Link>
            </div>

            <button type="submit" class="ui-btn-primary w-full" :disabled="form.processing">
                <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
                <UiIcon v-else name="logout" size="h-4 w-4" />
                {{ form.processing ? 'Ingresando...' : 'Iniciar sesión' }}
            </button>
        </form>

        <p class="mt-6 rounded-lg bg-slate-50 px-4 py-3 text-center text-xs text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
            ¿No tiene una cuenta? Contacte al administrador del sistema.
        </p>
    </GuestLayout>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'Login',
    components: { Head, Link, GuestLayout, FormField, UiIcon },
    props: {
        canResetPassword: { type: Boolean, default: false },
        status: { type: String, default: '' },
    },
    setup() {
        const form = useForm({
            email: '',
            password: '',
            remember: false,
        });

        const submit = () => {
            form.post(route('login'), {
                onFinish: () => form.reset('password'),
            });
        };

        return { form, submit };
    },
};
</script>
