<template>
    <label class="relative block w-full" :class="widthClass">
        <span class="sr-only">{{ label }}</span>
        <select
            :name="name"
            :value="modelValue"
            :disabled="disabled"
            class="ui-input appearance-none py-2.5 pe-9"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option value="">{{ placeholder }}</option>
            <option v-for="option in normalizedOptions" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <UiIcon name="chevron-down" size="h-4 w-4" class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-slate-400" />
    </label>
</template>

<script>
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'FilterDropdown',
    components: { UiIcon },
    props: {
        modelValue: { type: [String, Number], default: '' },
        options: { type: Array, default: () => [] },
        label: { type: String, default: 'Filtro' },
        placeholder: { type: String, default: 'Todos' },
        name: { type: String, default: 'filter' },
        disabled: { type: Boolean, default: false },
        width: { type: String, default: 'sm:w-56' },
    },
    emits: ['update:modelValue'],
    computed: {
        widthClass() {
            return this.width || '';
        },
        normalizedOptions() {
            return this.options.map((option) =>
                typeof option === 'object'
                    ? option
                    : { value: option, label: String(option) }
            );
        },
    },
};
</script>
