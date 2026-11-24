<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm" @click="$emit('close')" />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="open"
                ref="panel"
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-lg flex-col bg-white shadow-2xl dark:bg-slate-900"
                role="dialog"
                aria-modal="true"
                tabindex="-1"
                :aria-label="title"
            >
                <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ title }}</h2>
                        <p v-if="subtitle" class="mt-0.5 truncate text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <slot name="actions" />
                        <button
                            type="button"
                            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                            aria-label="Cerrar panel"
                            @click="$emit('close')"
                        >
                            <UiIcon name="close" size="h-5 w-5" />
                        </button>
                    </div>
                </header>

                <div class="flex-1 overflow-y-auto p-5">
                    <slot />
                </div>

                <footer v-if="$slots.footer" class="border-t border-slate-200 bg-slate-50/60 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/60">
                    <slot name="footer" />
                </footer>
            </aside>
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
    name: 'DetailDrawer',
    components: { UiIcon },
    props: {
        open: { type: Boolean, default: false },
        title: { type: String, default: 'Detalle' },
        subtitle: { type: String, default: '' },
    },
    emits: ['close'],
    data() {
        return {
            dialogId: createDialogId(),
            previousFocus: null,
            active: false,
        };
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
        onKeydown(event) {
            if (!this.open || !isTopDialog(this.dialogId)) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                this.$emit('close');
                return;
            }
            trapFocus(event, this.$refs.panel);
        },
    },
};
</script>
