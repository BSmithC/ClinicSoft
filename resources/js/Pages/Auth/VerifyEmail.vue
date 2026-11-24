<template>
    <GuestLayout>
        <Head title="Verificar correo electrónico" />

        <div class="mb-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                <UiIcon name="information" size="h-6 w-6" />
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Verificar correo electrónico</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Revise su bandeja de entrada y siga el enlace de verificación que le enviamos.
            </p>
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20"
            role="status"
        >
            Enviamos un nuevo enlace de verificación a su correo electrónico.
        </div>

        <form @submit.prevent="submit">
            <button type="submit" class="ui-btn-primary w-full" :disabled="form.processing">
                <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
                <UiIcon v-else name="check" size="h-4 w-4" />
                {{ form.processing ? 'Enviando...' : 'Reenviar enlace' }}
            </button>
        </form>

        <div class="mt-6 text-center">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800 hover:underline dark:text-slate-400 dark:hover:text-white"
            >
                <UiIcon name="logout" size="h-4 w-4" />
                Cerrar sesión
            </Link>
        </div>
    </GuestLayout>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'VerifyEmail',
    components: { Head, Link, GuestLayout, UiIcon },
    props: {
        status: { type: String, default: '' },
    },
    setup(props) {
        const form = useForm({});
        const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

        const submit = () => {
            form.post(route('verification.send'));
        };

        return { form, submit, verificationLinkSent };
    },
};
</script>
