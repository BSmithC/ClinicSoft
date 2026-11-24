<template>
    <section class="space-y-5">
        <header>
            <p class="text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">Seguridad</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Actualizar contraseña</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Use una contraseña larga y única para proteger su cuenta.
            </p>
        </header>

        <form class="space-y-5" @submit.prevent="updatePassword">
            <FormField label="Contraseña actual" name="current_password" required :error="form.errors.current_password">
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="ui-input"
                    required
                    autocomplete="current-password"
                />
            </FormField>

            <FormField label="Nueva contraseña" name="password" required :error="form.errors.password">
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="ui-input"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <FormField label="Confirmar nueva contraseña" name="password_confirmation" required :error="form.errors.password_confirmation">
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="ui-input"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <div class="flex flex-wrap items-center gap-4 border-t border-slate-200 pt-5 dark:border-slate-800">
                <button type="submit" class="ui-btn-primary" :disabled="form.processing">
                    <UiIcon name="check" size="h-4 w-4" />
                    Guardar contraseña
                </button>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                        <UiIcon name="check" size="h-4 w-4" />
                        Guardado
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'UpdatePasswordForm',
    components: { FormField, UiIcon },
    setup() {
        const passwordInput = ref(null);
        const currentPasswordInput = ref(null);
        const form = useForm({
            current_password: '',
            password: '',
            password_confirmation: '',
        });

        const updatePassword = () => {
            form.put(route('password.update'), {
                preserveScroll: true,
                onSuccess: () => form.reset(),
                onError: () => {
                    if (form.errors.password) {
                        form.reset('password', 'password_confirmation');
                        passwordInput.value?.focus();
                    }
                    if (form.errors.current_password) {
                        form.reset('current_password');
                        currentPasswordInput.value?.focus();
                    }
                },
            });
        };

        return { form, updatePassword, passwordInput, currentPasswordInput };
    },
};
</script>
