<template>
    <button
        type="button"
        class="ui-event w-full text-start"
        :class="[toneClass, positioned ? 'ui-event-positioned' : '', dense ? 'ui-event-dense' : '']"
        :title="tooltip"
        @click="$emit('click', event)"
    >
        <span class="flex items-start justify-between gap-2">
            <span class="flex min-w-0 items-center gap-1.5">
                <UiIcon :name="icon" size="h-3.5 w-3.5 shrink-0" />
                <span class="min-w-0 truncate font-semibold">{{ event.title || 'Sin título' }}</span>
            </span>
            <StatusBadge v-if="event.status" :status="event.status" dot class="ui-event-badge" />
        </span>
        <span class="mt-0.5 block truncate text-[11px] opacity-80">{{ timeLabel }}</span>
        <span v-if="event.subtitle || event.reason" class="mt-0.5 block truncate text-[11px] opacity-70">
            {{ event.subtitle || event.reason }}
        </span>
        <span class="mt-1 block text-[10px] font-bold uppercase tracking-wide opacity-70">
            {{ typeLabel }}<span v-if="event.reason && event.subtitle"> · {{ event.reason }}</span>
        </span>
    </button>
</template>

<script>
import StatusBadge from '@/Components/StatusBadge.vue';
import UiIcon from '@/Components/UiIcon.vue';
import { timeRangeLabel } from '@/utils/calendar';

export default {
    name: 'CalendarEvent',
    components: { StatusBadge, UiIcon },
    props: {
        event: { type: Object, required: true },
        positioned: { type: Boolean, default: false },
        dense: { type: Boolean, default: false },
    },
    emits: ['click'],
    computed: {
        type() {
            return this.event.type || 'appointment';
        },
        typeLabel() {
            if (this.type === 'session') return 'Sesión';
            if (this.type === 'block') return 'Bloqueo';
            return 'Cita';
        },
        icon() {
            if (this.type === 'session') return 'clipboard';
            if (this.type === 'block') return 'close';
            return 'user-md';
        },
        toneClass() {
            if (this.type === 'session') return 'ui-event-session';
            if (this.type === 'block') return 'ui-event-block';
            return 'ui-event-appointment';
        },
        timeLabel() {
            return timeRangeLabel(this.event.start, this.event.end);
        },
        tooltip() {
            return [this.typeLabel, this.event.title, this.timeLabel, this.event.subtitle, this.event.reason]
                .filter(Boolean)
                .join(' · ');
        },
    },
};
</script>
