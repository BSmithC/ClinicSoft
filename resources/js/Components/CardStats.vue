<template>
    <div class="ui-card p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    {{ statSubtitle }}
                </p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                    {{ statTitle }}
                </p>
            </div>
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white"
                :class="statIconColor"
            >
                <UiIcon :name="iconName" size="h-5 w-5" />
            </span>
        </div>

        <p v-if="showTrend" class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-1 font-semibold" :class="statPercentColor">
                <UiIcon
                    name="chevron-down"
                    size="h-3.5 w-3.5"
                    :class="statArrow === 'up' ? 'rotate-180' : ''"
                />
                {{ statPercent }}%
            </span>
            <span class="ms-1">{{ statDescripition }}</span>
        </p>
    </div>
</template>

<script>
import UiIcon from '@/Components/UiIcon.vue';

export default {
    name: 'CardStats',
    components: { UiIcon },
    props: {
        statSubtitle: { type: String, default: 'Pacientes' },
        statTitle: { type: [String, Number], default: 0 },
        statArrow: {
            type: String,
            default: 'up',
            validator: (value) => ['up', 'down'].includes(value),
        },
        statPercent: { type: [String, Number], default: 0 },
        statPercentColor: { type: String, default: 'text-emerald-600 dark:text-emerald-400' },
        statDescripition: { type: String, default: 'Respecto al periodo anterior' },
        showTrend: { type: Boolean, default: false },
        statIconName: { type: String, default: 'users' },
        statIconColor: { type: String, default: 'bg-brand-600' },
    },
    computed: {
        iconName() {
            return this.statIconName || 'users';
        },
    },
};
</script>
