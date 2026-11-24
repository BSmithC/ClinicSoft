<template>
    <section class="space-y-5">
        <header>
            <p class="text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">Zona de riesgo</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Eliminar cuenta</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Esta acción elimina permanentemente su cuenta y todos sus datos. Descargue antes cualquier información que desee conservar.
            </p>
        </header>

        <button
            type="button"
            class="ui-btn-secondary border-rose-200 text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-500/10"
            @click="confirmUserDeletion"
        >
            <UiIcon name="trash" size="h-4 w-4" />
            Eliminar cuenta
        </button>

        <ConfirmDialog
            :open="confirmingUserDeletion"
            title="Eliminar cuenta"
            message="Confirme su contraseña para eliminar su cuenta de forma definitiva. Esta acción no se puede deshacer."
            confirm-label="Eliminar definitivamente"
            tone="danger"
            :processing="form.processing"
            @cancel="closeModal"
            @confirm="deleteUser"
        >
            <FormField label="Contraseña" name="password" required hint="Ingrese su contraseña actual para continuar.">
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="ui-input"
                    placeholder="Contraseña"
                    autocomplete="current-password"
                    @keyup.enter="deleteUser"
                />
            </FormField>
        </ConfirmDialog>
    </section>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'DeleteUserForm',
    components: { ConfirmDialog, FormField, UiIcon },
    setup() {
        const confirmingUserDeletion = ref(false);
        const passwordInput = ref(null);
        const form = useForm({ password: '' });

        const confirmUserDeletion = () => {
            confirmingUserDeletion.value = true;
            nextTick(() => passwordInput.value?.focus());
        };

        const deleteUser = () => {
            form.delete(route('profile.destroy'), {
                preserveScroll: true,
                onSuccess: () => closeModal(),
                onError: () => passwordInput.value?.focus(),
                onFinish: () => form.reset(),
            });
        };

        const closeModal = () => {
            confirmingUserDeletion.value = false;
            form.reset();
        };

        return {
            confirmingUserDeletion,
            passwordInput,
            form,
            confirmUserDeletion,
            deleteUser,
            closeModal,
        };
    },
};
</script>
