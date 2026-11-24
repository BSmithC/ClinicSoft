<template>
    <GuestLayout>
        <Head title="Confirmar contraseña" />

        <div class="mb-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                <UiIcon name="settings" size="h-6 w-6" />
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Confirmar contraseña</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Esta es un área segura. Confirme su contraseña para continuar.
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Contraseña" name="password" required :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="ui-input"
                    required
                    autofocus
                    autocomplete="current-password"
                />
            </FormField>

            <button type="submit" class="ui-btn-primary w-full" :disabled="form.processing">
                <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
                <UiIcon v-else name="check" size="h-4 w-4" />
                {{ form.processing ? 'Verificando...' : 'Confirmar' }}
            </button>
        </form>
    </GuestLayout>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'ConfirmPassword',
    components: { Head, GuestLayout, FormField, UiIcon },
    setup() {
        const form = useForm({ password: '' });

        const submit = () => {
            form.post(route('password.confirm'), {
                onFinish: () => form.reset(),
            });
        };

        return { form, submit };
    },
};
</script>
