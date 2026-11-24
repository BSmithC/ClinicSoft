<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="cancel" />

                <div
                    v-if="open"
                    ref="panel"
                    class="ui-card relative w-full max-w-md p-6 shadow-2xl"
                    role="alertdialog"
                    aria-modal="true"
                    tabindex="-1"
                    :aria-label="title"
                >
                    <div class="flex items-start gap-4">
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full"
                            :class="tone === 'danger'
                                ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'
                                : 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300'"
                        >
                            <UiIcon :name="tone === 'danger' ? 'trash' : 'information'" size="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                                {{ title }}
                            </h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                                {{ message }}
                            </p>
                        </div>
                    </div>

                    <div v-if="$slots.default" class="mt-5">
                        <slot />
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" class="ui-btn-secondary" :disabled="processing" @click="cancel">
                            {{ cancelLabel }}
                        </button>
                        <button type="button" :class="confirmClass" :disabled="processing" @click="$emit('confirm')">
                            <span v-if="processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                            {{ confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import {
    createDialogId,
    focusFirst,
    isTopDialog,
    lockBodyScroll,
    registerDialog,
    restoreFocus,
    trapFocus,
    unregisterDialog,
    unlockBodyScroll,
} from '@/utils/overlay';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'ConfirmDialog',
    components: { UiIcon },
    props: {
        open: { type: Boolean, default: false },
        title: { type: String, default: 'Confirmar operación' },
        message: { type: String, default: '' },
        confirmLabel: { type: String, default: 'Confirmar' },
        cancelLabel: { type: String, default: 'Cancelar' },
        tone: { type: String, default: 'danger' },
        processing: { type: Boolean, default: false },
    },
    emits: ['confirm', 'cancel'],
    data() {
        return {
            dialogId: createDialogId(),
            previousFocus: null,
            active: false,
        };
    },
    computed: {
        confirmClass() {
            return this.tone === 'danger'
                ? 'ui-btn-primary bg-rose-600 hover:bg-rose-700 focus:ring-rose-500'
                : 'ui-btn-primary';
        },
    },
    watch: {
        open: {
            immediate: true,
            handler(value) {
                if (value) this.activate();
                else this.deactivate();
            },
        },
    },
    mounted() {
        document.addEventListener('keydown', this.onKeydown);
        if (this.open) this.focusPanel();
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.onKeydown);
        this.deactivate();
    },
    methods: {
        activate() {
            if (this.active) return;
            this.active = true;
            this.previousFocus = document.activeElement;
            registerDialog(this.dialogId, () => this.$refs.panel || null);
            lockBodyScroll();
            this.focusPanel();
        },
        deactivate() {
            if (!this.active) return;
            this.active = false;
            unregisterDialog(this.dialogId);
            unlockBodyScroll();
            const target = this.previousFocus;
            this.previousFocus = null;
            if (target) this.$nextTick(() => restoreFocus(target));
        },
        focusPanel() {
            this.$nextTick(() => focusFirst(this.$refs.panel));
        },
        cancel() {
            if (!this.processing) this.$emit('cancel');
        },
        onKeydown(event) {
            if (!this.open || !isTopDialog(this.dialogId)) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                this.cancel();
                return;
            }
            trapFocus(event, this.$refs.panel);
        },
    },
};
</script>
