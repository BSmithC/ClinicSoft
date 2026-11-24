<template>
    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
        <Link :href="cancelHref" class="ui-btn-secondary">
            <UiIcon name="arrow-left" size="h-4 w-4" />
            {{ cancelLabel }}
        </Link>
        <button type="submit" class="ui-btn-primary" :disabled="processing">
            <span v-if="processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true" />
            <UiIcon v-else name="check" size="h-4 w-4" />
            {{ label }}
        </button>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'FormActions',
    components: { Link, UiIcon },
    props: {
        cancelHref: { type: String, required: true },
        label: { type: String, default: 'Guardar' },
        cancelLabel: { type: String, default: 'Cancelar' },
        processing: { type: Boolean, default: false },
    },
};
</script>
