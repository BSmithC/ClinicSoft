<template>
    <GuestLayout>
        <Head title="Restablecer contraseña" />

        <div class="mb-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                <UiIcon name="check" size="h-6 w-6" />
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Restablecer contraseña</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Defina una nueva contraseña para su cuenta.
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Correo electrónico" name="email" required :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="ui-input"
                    required
                    autofocus
                    autocomplete="username"
                />
            </FormField>

            <FormField label="Nueva contraseña" name="password" required :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="ui-input"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <FormField label="Confirmar contraseña" name="password_confirmation" required :error="form.errors.password_confirmation">
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="ui-input"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <button type="submit" class="ui-btn-primary w-full" :disabled="form.processing">
                <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
                <UiIcon v-else name="check" size="h-4 w-4" />
                {{ form.processing ? 'Guardando...' : 'Guardar contraseña' }}
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
    name: 'ResetPassword',
    components: { Head, GuestLayout, FormField, UiIcon },
    props: {
        email: { type: String, required: true },
        token: { type: String, required: true },
    },
    setup(props) {
        const form = useForm({
            token: props.token,
            email: props.email,
            password: '',
            password_confirmation: '',
        });

        const submit = () => {
            form.post(route('password.store'), {
                onFinish: () => form.reset('password', 'password_confirmation'),
            });
        };

        return { form, submit };
    },
};
</script>
