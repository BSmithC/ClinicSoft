<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="visible"
            class="fixed bottom-5 right-5 z-[70] flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3 shadow-xl"
            :class="toneClass"
            role="status"
            aria-live="polite"
        >
            <UiIcon :name="tone === 'error' ? 'information' : 'check'" size="h-5 w-5" class="mt-0.5 shrink-0" />
            <div class="min-w-0 flex-1">
                <p v-if="heading" class="text-sm font-semibold">{{ heading }}</p>
                <p class="text-sm">{{ message }}</p>
            </div>
            <button type="button" class="shrink-0 text-xs font-semibold uppercase opacity-70 hover:opacity-100" aria-label="Cerrar notificación" @click="$emit('close')">
                Cerrar
            </button>
        </div>
    </Transition>
</template>

<script>
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'ToastNotification',
    components: { UiIcon },
    props: {
        visible: { type: Boolean, default: false },
        message: { type: String, default: '' },
        heading: { type: String, default: '' },
        tone: { type: String, default: 'success' },
        duration: { type: Number, default: 6000 },
    },
    emits: ['close'],
    data() {
        return {
            timer: null,
        };
    },
    computed: {
        toneClass() {
            if (this.tone === 'error') {
                return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200';
            }
            if (this.tone === 'info') {
                return 'border-brand-200 bg-brand-50 text-brand-800 dark:border-brand-900 dark:bg-brand-950 dark:text-brand-200';
            }
            return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200';
        },
    },
    watch: {
        visible(value) {
            this.clearTimer();
            if (value && this.duration > 0) {
                this.timer = window.setTimeout(() => this.$emit('close'), this.duration);
            }
        },
    },
    beforeUnmount() {
        this.clearTimer();
    },
    methods: {
        clearTimer() {
            if (this.timer) {
                window.clearTimeout(this.timer);
                this.timer = null;
            }
        },
    },
};
</script>
