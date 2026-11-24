<template>
    <div>
        <label :for="name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ label }}
            <span v-if="required" class="text-rose-500" aria-hidden="true">*</span>
            <span v-if="required" class="sr-only">(obligatorio)</span>
        </label>

        <slot />

        <p v-if="hint && !error" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
            {{ hint }}
        </p>
        <p v-if="error" class="mt-1.5 flex items-start gap-1.5 text-sm text-rose-600 dark:text-rose-400" role="alert">
            <UiIcon name="information" size="h-4 w-4" class="mt-0.5 shrink-0" />
            <span>{{ error }}</span>
        </p>
    </div>
</template>

<script>
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'FormField',
    components: { UiIcon },
    props: {
        label: { type: String, required: true },
        name: { type: String, required: true },
        required: { type: Boolean, default: false },
        hint: { type: String, default: '' },
        error: { type: String, default: '' },
    },
};
</script>
