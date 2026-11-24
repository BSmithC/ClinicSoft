<template>
    <Head :title="`Medicamento ${drugs.name ?? ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                eyebrow="Catálogo clínico"
                :title="drugs.name ?? `Medicamento #${drugs.id}`"
                description="Detalle del medicamento registrado."
            >
                <template #actions>
                    <Link :href="route('drugs.index')" class="ui-btn-secondary">
                        <UiIcon name="arrow-left" size="h-4 w-4" />
                        Volver al listado
                    </Link>
                    <Link :href="route('drugs.edit', drugs.id)" class="ui-btn-primary">
                        <UiIcon name="pencil" size="h-4 w-4" />
                        Editar
                    </Link>
                </template>
            </PageHeader>
        </template>

        <section class="ui-card max-w-4xl p-6">
            <dl class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                <div v-for="item in fields" :key="item.label" class="border-b border-slate-100 pb-4 last:border-0 dark:border-slate-800">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ item.label }}</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-white">{{ item.value }}</dd>
                </div>
            </dl>
        </section>
    </AuthenticatedLayout>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        PageHeader,
        UiIcon,
    },
    props: {
        drugs: { type: Object, required: true },
    },
    computed: {
        fields() {
            return [
                { label: 'Identificador', value: `#${this.drugs.id}` },
                { label: 'Nombre', value: this.drugs.name || '—' },
                { label: 'Vía de administración', value: this.drugs.route || '—' },
                { label: 'Frecuencia', value: this.drugs.frequency || '—' },
                { label: 'Duración', value: this.drugs.duration || '—' },
                { label: 'Descripción', value: this.drugs.description || '—' },
            ];
        },
    },
};
</script>
