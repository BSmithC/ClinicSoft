<template>
    <component
        :is="href ? Link : 'div'"
        v-bind="href ? { href } : {}"
        class="ui-card group flex min-w-0 items-start justify-between gap-4 p-5 transition duration-200"
        :class="href ? 'hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-card-hover dark:hover:border-brand-800' : ''"
    >
        <div class="min-w-0">
            <p class="truncate text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ label }}
            </p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ value }}
            </p>
            <p v-if="hint" class="mt-1 truncate text-xs text-slate-400 dark:text-slate-500">
                {{ hint }}
            </p>
        </div>
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="toneClass">
            <UiIcon :name="icon" size="h-5 w-5" />
        </span>
    </component>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'StatCard',
    components: { Link, UiIcon },
    props: {
        label: { type: String, required: true },
        value: { type: [String, Number], default: 0 },
        icon: { type: String, default: 'chart' },
        tone: { type: String, default: 'brand' },
        hint: { type: String, default: '' },
        href: { type: String, default: '' },
    },
    data() {
        return {
            tones: {
                brand: 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
                sky: 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
                amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
                emerald: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
                rose: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
                violet: 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
                slate: 'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300',
            },
        };
    },
    computed: {
        toneClass() {
            return this.tones[this.tone] ?? this.tones.brand;
        },
    },
};
</script>
