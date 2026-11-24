<template>
    <div class="relative w-full" :class="widthClass">
        <UiIcon name="search" size="h-4 w-4" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
            :id="inputId"
            :name="name"
            type="search"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            class="ui-input py-2.5 ps-10"
            autocomplete="off"
            @input="$emit('update:modelValue', $event.target.value)"
            @keyup.enter="$emit('search')"
        />
    </div>
</template>

<script>
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'SearchInput',
    components: { UiIcon },
    props: {
        modelValue: { type: [String, Number], default: '' },
        placeholder: { type: String, default: 'Buscar...' },
        name: { type: String, default: 'search' },
        inputId: { type: String, default: 'search-input' },
        disabled: { type: Boolean, default: false },
        width: { type: String, default: 'sm:w-72' },
    },
    emits: ['update:modelValue', 'search'],
    computed: {
        widthClass() {
            return this.width || '';
        },
    },
};
</script>
