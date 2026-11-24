<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2 px-1 text-xs text-slate-500 dark:text-slate-400">
            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ periodLabel }}</p>
            <p v-if="timezone">Zona horaria de la clínica: {{ timezone }}</p>
        </div>

        <!-- Mes: grilla real de 6 semanas -->
        <section v-if="view === 'month'" class="ui-card hidden overflow-hidden sm:block">
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50/70 dark:border-slate-800 dark:bg-slate-800/40">
                <div v-for="label in weekdayHeaders" :key="label" class="ui-th py-2 text-center">
                    {{ label }}
                </div>
            </div>
            <div class="ui-calendar-grid grid grid-cols-7">
                <article
                    v-for="cell in monthCells"
                    :key="`month-${cell.date}`"
                    class="ui-calendar-cell"
                    :class="{ 'ui-calendar-today': cell.isToday, 'ui-calendar-outside': !cell.inMonth }"
                >
                    <button type="button" class="ui-calendar-day-button" @click="$emit('select-date', cell.date)">
                        <span>{{ cell.dayNumber }}</span>
                        <span v-if="cell.isToday" class="rounded-full bg-brand-600 px-1.5 py-0.5 text-[9px] font-bold uppercase text-white">Hoy</span>
                    </button>

                    <div class="mt-1 space-y-1">
                        <CalendarEvent
                            v-for="event in cell.events"
                            :key="event.id"
                            :event="event"
                            dense
                            @click="$emit('select-event', event)"
                        />
                        <button
                            v-if="cell.events.length > 3"
                            type="button"
                            class="text-[10px] font-semibold text-brand-600 hover:underline dark:text-brand-400"
                            @click="$emit('select-date', cell.date)"
                        >
                            +{{ cell.events.length - 3 }} más
                        </button>
                    </div>

                    <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-0.5 pt-1 text-[10px] text-slate-500 dark:text-slate-400">
                        <span>{{ cell.summary.appointments }} citas</span>
                        <span>{{ cell.summary.sessions }} sesiones</span>
                        <span class="text-emerald-600 dark:text-emerald-400">{{ cell.summary.free }} libres</span>
                        <span v-if="cell.summary.blocked" class="text-rose-600 dark:text-rose-400">
                            Bloqueado
                        </span>
                    </div>
                </article>
            </div>
        </section>

        <!-- Semana: eje horario 08:00 - 18:00 -->
        <section v-else-if="view === 'week'" class="ui-card hidden overflow-hidden sm:block">
            <div class="grid border-b border-slate-200 dark:border-slate-800" :style="weekGridStyle">
                <div />
                <button
                    v-for="day in days"
                    :key="`week-head-${day}`"
                    type="button"
                    class="px-2 py-2 text-start transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                    @click="$emit('select-date', day)"
                >
                    <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        {{ formatWeekday(day) }}
                    </span>
                    <span class="block text-sm font-semibold text-slate-800 dark:text-white">{{ formatShortDate(day) }}</span>
                    <span v-if="day === today" class="text-[10px] font-bold uppercase text-brand-600 dark:text-brand-400">Hoy</span>
                </button>
            </div>
            <div class="ui-time-grid overflow-x-auto">
                <div class="grid" :style="{ ...weekGridStyle, height: gridHeight }">
                    <div class="ui-time-axis">
                        <span
                            v-for="(hour, index) in hours"
                            :key="`week-hour-${hour}`"
                            class="ui-time-label"
                            :style="{ top: `${(index / (hours.length - 1)) * 100}%` }"
                        >{{ hour }}</span>
                    </div>
                    <div v-for="day in days" :key="`week-col-${day}`" class="ui-time-column">
                        <div v-for="line in hourLines" :key="`week-line-${day}-${line}`" class="ui-time-line" :style="{ top: line }" />
                        <button
                            v-for="event in allDayEventsFor(day)"
                            :key="`week-allday-${event.id}`"
                            type="button"
                            class="ui-all-day-banner"
                            @click="$emit('select-event', event)"
                        >
                            {{ event.title || 'Bloqueo' }}
                        </button>
                        <CalendarEvent
                            v-for="layout in timedLayoutsFor(day)"
                            :key="`week-event-${layout.event.id}`"
                            :event="layout.event"
                            positioned
                            class="absolute z-10 overflow-hidden"
                            :style="layout.style"
                            @click="$emit('select-event', layout.event)"
                        />
                        <button
                            v-for="slot in availableSlotsForDate(day)"
                            :key="`week-slot-${day}-${slot.sessionId}-${slot.start}`"
                            type="button"
                            class="ui-slot-available absolute z-0 truncate px-1 text-[10px] font-semibold"
                            :style="slotStyle(slot)"
                            :title="`Horario disponible: ${formatTime(slot.start)}`"
                            @click="emitSlot(slot, day)"
                        >
                            {{ formatTime(slot.start) }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Día: franjas horarias con eventos posicionados -->
        <section v-else class="ui-card hidden overflow-hidden sm:block">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <div>
                    <p class="text-sm font-semibold text-slate-800 dark:text-white">{{ formatLongDate(currentDate) }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ summaryFor(currentDate).appointments }} citas · {{ summaryFor(currentDate).sessions }} sesiones ·
                        {{ summaryFor(currentDate).free }} horarios libres
                    </p>
                </div>
                <span v-if="currentDate === today" class="rounded-full bg-brand-600 px-2.5 py-1 text-[10px] font-bold uppercase text-white">Hoy</span>
            </header>
            <div class="ui-time-grid overflow-x-auto">
                <div class="grid" :style="{ ...dayGridStyle, height: gridHeight }">
                    <div class="ui-time-axis">
                        <span
                            v-for="(hour, index) in hours"
                            :key="`day-hour-${hour}`"
                            class="ui-time-label"
                            :style="{ top: `${(index / (hours.length - 1)) * 100}%` }"
                        >{{ hour }}</span>
                    </div>
                    <div class="ui-time-column">
                        <div v-for="line in hourLines" :key="`day-line-${line}`" class="ui-time-line" :style="{ top: line }" />
                        <button
                            v-for="event in allDayEventsFor(currentDate)"
                            :key="`day-allday-${event.id}`"
                            type="button"
                            class="ui-all-day-banner"
                            @click="$emit('select-event', event)"
                        >
                            {{ event.title || 'Bloqueo' }}
                        </button>
                        <CalendarEvent
                            v-for="layout in timedLayoutsFor(currentDate)"
                            :key="`day-event-${layout.event.id}`"
                            :event="layout.event"
                            positioned
                            class="absolute z-10 overflow-hidden"
                            :style="layout.style"
                            @click="$emit('select-event', layout.event)"
                        />
                        <button
                            v-for="slot in availableSlotsForDate(currentDate)"
                            :key="`day-slot-${slot.sessionId}-${slot.start}`"
                            type="button"
                            class="ui-slot-available absolute z-0 truncate px-1 text-[10px] font-semibold"
                            :style="slotStyle(slot)"
                            :title="`Horario disponible: ${formatTime(slot.start)}`"
                            @click="emitSlot(slot, currentDate)"
                        >
                            {{ formatTime(slot.start) }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200 p-3 dark:border-slate-800">
                <p class="mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Horarios disponibles</p>
                <div v-if="availableSlotsForDate(currentDate).length" class="flex flex-wrap gap-2">
                    <button
                        v-for="slot in availableSlotsForDate(currentDate)"
                        :key="`day-list-${slot.sessionId}-${slot.start}`"
                        type="button"
                        class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"
                        @click="emitSlot(slot, currentDate)"
                    >
                        {{ formatTime(slot.start) }} – {{ formatTime(slot.end) }}
                    </button>
                </div>
                <p v-else class="text-xs text-slate-400 dark:text-slate-500">No hay horarios libres publicados para esta fecha.</p>
            </div>
        </section>

        <!-- Móvil: lista compacta con scroll horizontal controlado -->
        <section class="ui-card sm:hidden">
            <header class="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2 dark:border-slate-800">
                <p class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">{{ periodLabel }}</p>
                <div class="flex shrink-0 gap-1">
                    <button type="button" class="ui-btn-ghost h-8 w-8 px-0" aria-label="Desplazar a la izquierda" @click="scrollCompact(-1)">
                        <UiIcon name="chevron-left" size="h-4 w-4" />
                    </button>
                    <button type="button" class="ui-btn-ghost h-8 w-8 px-0" aria-label="Desplazar a la derecha" @click="scrollCompact(1)">
                        <UiIcon name="chevron-right" size="h-4 w-4" />
                    </button>
                </div>
            </header>
            <div ref="compactList" class="flex snap-x gap-2 overflow-x-auto p-3">
                <article
                    v-for="day in compactDays"
                    :key="`compact-${day}`"
                    class="w-52 shrink-0 snap-start rounded-lg border border-slate-200 p-2 dark:border-slate-700"
                    :class="{ 'ui-calendar-today': day === today }"
                >
                    <button type="button" class="ui-calendar-day-button" @click="$emit('select-date', day)">
                        <span>{{ formatWeekday(day) }} {{ formatShortDate(day) }}</span>
                        <span v-if="day === today" class="rounded-full bg-brand-600 px-1.5 py-0.5 text-[9px] font-bold uppercase text-white">Hoy</span>
                    </button>
                    <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">
                        {{ summaryFor(day).appointments }} citas · {{ summaryFor(day).free }} libres
                    </p>
                    <div class="mt-2 space-y-1">
                        <CalendarEvent
                            v-for="event in eventsFor(day).slice(0, 3)"
                            :key="`compact-event-${event.id}`"
                            :event="event"
                            dense
                            @click="$emit('select-event', event)"
                        />
                        <p v-if="!eventsFor(day).length" class="py-2 text-center text-[11px] text-slate-400 dark:text-slate-500">Sin eventos</p>
                    </div>
                    <div v-if="availableSlotsForDate(day).length" class="mt-2 flex flex-wrap gap-1">
                        <button
                            v-for="slot in availableSlotsForDate(day).slice(0, 3)"
                            :key="`compact-slot-${day}-${slot.sessionId}-${slot.start}`"
                            type="button"
                            class="rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"
                            @click="emitSlot(slot, day)"
                        >
                            {{ formatTime(slot.start) }}
                        </button>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>

<script>
import {
    addDays,
    dayNumber,
    eachDay,
    formatLongDate,
    formatMonthLabel,
    formatShortDate,
    formatTime,
    formatWeekday,
    layoutTimedEvents,
    monthGrid,
    normalizeDate,
    parseSlotDateTime,
    rangeForView,
    startOfWeek,
    summaryForDate,
    todayISO,
} from '@/utils/calendar';
import CalendarEvent from '@/Components/Calendar/CalendarEvent.vue';
import UiIcon from '@/Components/UiIcon.vue';

const DAY_START = 8 * 60;
const DAY_END = 18 * 60;

export default {
    name: 'AppointmentCalendar',
    components: { CalendarEvent, UiIcon },
    props: {
        events: { type: Array, default: () => [] },
        sessions: { type: Array, default: () => [] },
        availabilitySlots: { type: Object, default: () => ({}) },
        blockedDates: { type: Array, default: () => [] },
        view: { type: String, default: 'month' },
        focusDate: { type: String, default: '' },
        daySummary: { type: Object, default: () => ({}) },
        timezone: { type: String, default: '' },
    },
    emits: ['select-date', 'select-event', 'select-slot'],
    data() {
        return {
            today: todayISO(),
            weekdayHeaders: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            hours: Array.from({ length: 11 }, (_, index) => `${String(index + 8).padStart(2, '0')}:00`),
        };
    },
    computed: {
        currentDate() {
            return normalizeDate(this.focusDate) || this.today;
        },
        days() {
            if (this.view === 'day') return [this.currentDate];
            if (this.view === 'week') {
                const first = startOfWeek(this.currentDate);
                return eachDay(first, addDays(first, 6));
            }
            return monthGrid(this.currentDate);
        },
        compactDays() {
            if (this.view === 'month') return this.days;
            if (this.view === 'week') return eachDay(rangeForView('week', this.currentDate).from, rangeForView('week', this.currentDate).to);
            return [this.currentDate];
        },
        monthCells() {
            const month = this.currentDate.slice(0, 7);
            return this.days.map((date) => ({
                date,
                dayNumber: dayNumber(date),
                inMonth: date.slice(0, 7) === month,
                isToday: date === this.today,
                events: this.eventsFor(date).slice(0, 3),
                summary: this.summaryFor(date),
            }));
        },
        eventsByDate() {
            return (this.events || []).reduce((grouped, event) => {
                const date = normalizeDate(event.date);
                if (!date) return grouped;
                if (!grouped[date]) grouped[date] = [];
                grouped[date].push(event);
                return grouped;
            }, {});
        },
        hourLines() {
            return this.hours.map((_, index) => `${(index / (this.hours.length - 1)) * 100}%`);
        },
        gridHeight() {
            return `${this.hours.length * 56}px`;
        },
        weekGridStyle() {
            return { gridTemplateColumns: '60px repeat(7, minmax(160px, 1fr))' };
        },
        dayGridStyle() {
            return { gridTemplateColumns: '60px minmax(0, 1fr)' };
        },
        periodLabel() {
            if (this.view === 'month') return formatMonthLabel(this.currentDate);
            if (this.view === 'week') {
                const days = this.compactDays;
                return `${formatShortDate(days[0])} – ${formatShortDate(days[days.length - 1])}`;
            }
            return formatLongDate(this.currentDate);
        },
    },
    methods: {
        eventsFor(date) {
            return this.eventsByDate[date] || [];
        },
        allDayEventsFor(date) {
            return this.eventsFor(date).filter((event) => event.type === 'block' || !event.start);
        },
        timedLayoutsFor(date) {
            return layoutTimedEvents(
                this.eventsFor(date).filter((event) => event.start && event.type !== 'block'),
                DAY_START,
                DAY_END
            );
        },
        sessionFor(sessionId) {
            return (this.sessions || []).find((session) => String(session.id) === String(sessionId));
        },
        doctorIdForSession(session) {
            if (!session) return null;
            return session.doctor_id ?? session.doctorId ?? session.doctor?.id ?? session.user_id ?? null;
        },
        availableSlotsForDate(date) {
            return Object.entries(this.availabilitySlots || {}).flatMap(([sessionId, slots]) => {
                const session = this.sessionFor(sessionId);
                if (!session || normalizeDate(session.date) !== date) return [];
                return (slots || [])
                    .filter((slot) => slot.available)
                    .map((slot) => ({
                        ...slot,
                        sessionId: session.id,
                        doctorId: this.doctorIdForSession(session),
                    }));
            });
        },
        summaryFor(date) {
            return summaryForDate({
                date,
                events: this.events,
                sessions: this.sessions,
                availabilitySlots: this.availabilitySlots,
                blockedDates: this.blockedDates,
                stored: this.daySummary?.[date] ?? null,
            });
        },
        slotStyle(slot) {
            return layoutTimedEvents([{ start: slot.start, end: slot.end }], DAY_START, DAY_END)[0]?.style || {};
        },
        emitSlot(slot, date) {
            const parsed = parseSlotDateTime(date, slot.start, slot.end);
            if (!parsed) return;
            this.$emit('select-slot', {
                date: parsed.date,
                start: parsed.start,
                end: parsed.end || parsed.start,
                sessionId: slot.sessionId ?? null,
                doctorId: slot.doctorId ?? null,
            });
        },
        scrollCompact(direction) {
            const list = this.$refs.compactList;
            if (!list || typeof list.scrollBy !== 'function') return;
            list.scrollBy({ left: direction * 336, behavior: 'smooth' });
        },
        formatTime,
        formatShortDate,
        formatWeekday,
        formatLongDate,
    },
};
</script>
