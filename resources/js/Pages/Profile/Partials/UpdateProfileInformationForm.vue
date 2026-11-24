<template>
    <section class="space-y-5">
        <header>
            <p class="text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">Datos personales</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Información del perfil</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                Actualice su nombre y correo electrónico.
            </p>
        </header>

        <form class="space-y-5" @submit.prevent="save">
            <FormField label="Nombre" name="name" required :error="form.errors.name">
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="ui-input"
                    required
                    autofocus
                    autocomplete="name"
                />
            </FormField>

            <FormField label="Correo electrónico" name="email" required :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="ui-input"
                    required
                    autocomplete="username"
                />
            </FormField>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20">
                <p>
                    Su correo electrónico todavía no está verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="ms-1 font-semibold underline hover:no-underline"
                    >
                        Reenviar correo de verificación
                    </Link>
                </p>
                <p v-if="status === 'verification-link-sent'" class="mt-2 font-medium">
                    Enviamos un nuevo enlace de verificación a su correo.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 border-t border-slate-200 pt-5 dark:border-slate-800">
                <button type="submit" class="ui-btn-primary" :disabled="form.processing">
                    <UiIcon name="check" size="h-4 w-4" />
                    Guardar cambios
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
import { Link, useForm, usePage } from '@inertiajs/vue3';
import FormField from '@/Components/FormField.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'UpdateProfileInformationForm',
    components: { Link, FormField, UiIcon },
    props: {
        mustVerifyEmail: { type: Boolean, default: false },
        status: { type: String, default: '' },
    },
    setup() {
        const user = usePage().props.auth.user;
        const form = useForm({
            name: user.name,
            email: user.email,
        });

        const save = () => {
            form.patch(route('profile.update'), { preserveScroll: true });
        };

        return { user, form, save };
    },
};
</script>
