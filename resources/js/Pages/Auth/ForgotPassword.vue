<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <div class="mb-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                <UiIcon name="information" size="h-6 w-6" />
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Recuperar contraseña</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Ingrese su correo electrónico y le enviaremos un enlace para establecer una nueva contraseña.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20"
            role="status"
        >
            {{ status }}
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

            <button type="submit" class="ui-btn-primary w-full" :disabled="form.processing">
                <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
                <UiIcon v-else name="check" size="h-4 w-4" />
                {{ form.processing ? 'Enviando...' : 'Enviar enlace' }}
            </button>
        </form>

        <div class="mt-6 text-center">
            <Link :href="route('login')" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">
                <UiIcon name="arrow-left" size="h-4 w-4" />
                Volver al inicio de sesión
            </Link>
        </div>
    </GuestLayout>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'ForgotPassword',
    components: { Head, Link, GuestLayout, FormField, UiIcon },
    props: {
        status: { type: String, default: '' },
    },
    setup() {
        const form = useForm({ email: '' });

        const submit = () => {
            form.post(route('password.email'));
        };

        return { form, submit };
    },
};
</script>
