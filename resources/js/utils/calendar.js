/**
 * Pure date/time helpers shared by the calendar UI.
 * Dates are handled as Y-m-d strings; date-only math uses UTC to avoid
 * timezone shifts. Time strings are displayed as they come from the server.
 */

const pad = (value, length = 2) => String(value).padStart(length, '0');

export function normalizeDate(value) {
    if (!value) return '';
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return match ? `${match[1]}-${match[2]}-${match[3]}` : '';
}

function toUTCDate(value) {
    const normalized = normalizeDate(value);
    if (!normalized) return null;
    const [year, month, day] = normalized.split('-').map(Number);
    const date = new Date(Date.UTC(year, month - 1, day));
    return Number.isNaN(date.getTime()) ? null : date;
}

function toISODate(date) {
    return date.toISOString().slice(0, 10);
}

export function todayISO() {
    const now = new Date();
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

export function addDays(dateString, amount = 0) {
    const date = toUTCDate(dateString);
    if (!date) return normalizeDate(dateString);
    date.setUTCDate(date.getUTCDate() + Number(amount || 0));
    return toISODate(date);
}

export function addMonths(dateString, amount = 0) {
    const date = toUTCDate(dateString);
    if (!date) return normalizeDate(dateString);
    const day = date.getUTCDate();
    date.setUTCDate(1);
    date.setUTCMonth(date.getUTCMonth() + Number(amount || 0));
    const lastDay = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate();
    date.setUTCDate(Math.min(day, lastDay));
    return toISODate(date);
}

export function startOfWeek(dateString, weekStartsOn = 1) {
    const date = toUTCDate(dateString);
    if (!date) return '';
    const difference = (date.getUTCDay() - weekStartsOn + 7) % 7;
    date.setUTCDate(date.getUTCDate() - difference);
    return toISODate(date);
}

export function startOfMonth(dateString) {
    const date = toUTCDate(dateString);
    if (!date) return '';
    date.setUTCDate(1);
    return toISODate(date);
}

export function endOfMonth(dateString) {
    const date = toUTCDate(dateString);
    if (!date) return '';
    return toISODate(new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)));
}

export function eachDay(from, to) {
    const start = normalizeDate(from);
    const end = normalizeDate(to);
    if (!start || !end || end < start) return [];
    const days = [];
    let cursor = start;
    while (cursor <= end && days.length < 400) {
        days.push(cursor);
        cursor = addDays(cursor, 1);
    }
    return days;
}

export function monthGrid(dateString) {
    const first = startOfWeek(startOfMonth(dateString) || todayISO());
    return Array.from({ length: 42 }, (_, index) => addDays(first, index));
}

export function rangeForView(view, dateString) {
    const date = normalizeDate(dateString) || todayISO();
    if (view === 'month') {
        return { from: startOfMonth(date), to: endOfMonth(date) };
    }
    if (view === 'day') {
        return { from: date, to: date };
    }
    const first = startOfWeek(date);
    return { from: first, to: addDays(first, 6) };
}

export function formatLongDate(dateString) {
    const date = toUTCDate(dateString);
    if (!date) return '—';
    return new Intl.DateTimeFormat('es-ES', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(date);
}

export function formatMonthLabel(dateString) {
    const date = toUTCDate(dateString);
    if (!date) return '—';
    const label = new Intl.DateTimeFormat('es-ES', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(date);
    return label.charAt(0).toUpperCase() + label.slice(1);
}

export function formatShortDate(dateString) {
    const date = toUTCDate(dateString);
    if (!date) return '—';
    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit',
        month: 'short',
        timeZone: 'UTC',
    }).format(date);
}

export function formatWeekday(dateString, long = false) {
    const date = toUTCDate(dateString);
    if (!date) return '—';
    const label = new Intl.DateTimeFormat('es-ES', {
        weekday: long ? 'long' : 'short',
        timeZone: 'UTC',
    }).format(date);
    return label.charAt(0).toUpperCase() + label.slice(1).replace('.', '');
}

export function dayNumber(dateString) {
    return normalizeDate(dateString).slice(-2) || '—';
}

export function formatTime(time) {
    if (time === null || time === undefined || time === '') return '';
    const match = String(time).trim().match(/^(\d{1,2}):(\d{2})/);
    if (!match) return String(time).slice(0, 5);
    return `${pad(match[1])}:${match[2]}`;
}

export function toTimeWithSeconds(time) {
    const formatted = formatTime(time);
    return formatted ? `${formatted}:00` : '';
}

export function minutesFromTime(time) {
    const formatted = formatTime(time);
    if (!formatted) return null;
    const [hours, minutes] = formatted.split(':').map(Number);
    return hours * 60 + minutes;
}

function minutesToTime(minutes) {
    const safe = Math.max(0, Math.min(Number(minutes) || 0, 23 * 60 + 59));
    return `${pad(Math.floor(safe / 60))}:${pad(safe % 60)}`;
}

export function parseSlotDateTime(dateString, start, end = '', defaultMinutes = 30) {
    const date = normalizeDate(dateString);
    const startTime = toTimeWithSeconds(start);
    if (!date || !startTime) return null;
    const startMinutes = minutesFromTime(start);
    const endMinutes = minutesFromTime(end);
    const resolvedEndMinutes = endMinutes === null ? startMinutes + Number(defaultMinutes || 30) : endMinutes;
    const endTime = toTimeWithSeconds(minutesToTime(resolvedEndMinutes));
    return {
        date,
        start: startTime,
        end: endTime,
        startMinutes,
        endMinutes: resolvedEndMinutes,
        startLabel: formatTime(start),
        endLabel: formatTime(endTime),
    };
}

export function timeRangeLabel(start, end) {
    const first = formatTime(start);
    const last = formatTime(end);
    if (!first && !last) return 'Todo el día';
    if (!first) return `Hasta ${last}`;
    if (!last) return `Desde ${first}`;
    return `${first} – ${last}`;
}

/** Counters for a single day, preferring the server summary when available. */
export function summaryForDate({ date, events = [], sessions = [], availabilitySlots = {}, blockedDates = [], stored = null }) {
    if (stored) {
        return {
            appointments: Number(stored.appointments ?? 0),
            sessions: Number(stored.sessions ?? 0),
            free: Number(stored.free ?? 0),
            blocked: Number(stored.blocked ?? 0),
        };
    }
    const day = normalizeDate(date);
    const dayEvents = events.filter((event) => normalizeDate(event.date) === day);
    const blocks = dayEvents.filter((event) => event.type === 'block');
    let free = 0;

    Object.entries(availabilitySlots || {}).forEach(([sessionId, slots]) => {
        const session = sessions.find((item) => String(item.id) === String(sessionId));
        if (!session || normalizeDate(session.date) !== day) return;
        free += (slots || []).filter((slot) => slot.available).length;
    });

    return {
        appointments: dayEvents.filter((event) => event.type === 'appointment').length,
        sessions: dayEvents.filter((event) => event.type === 'session').length,
        free,
        blocked: blocks.length || (blockedDates || []).filter((item) => normalizeDate(item.date) === day).length,
    };
}

/**
 * Places timed events inside a fixed hour band with side-by-side columns for overlaps.
 * Returned styles are percentages so they can be used by absolute-positioned elements.
 */
export function layoutTimedEvents(events, dayStart = 8 * 60, dayEnd = 18 * 60) {
    const span = Math.max(dayEnd - dayStart, 1);
    const items = (events || [])
        .map((event) => {
            const rawStart = minutesFromTime(event.start);
            if (rawStart === null) return null;
            const rawEnd = minutesFromTime(event.end);
            const desiredEnd = rawEnd === null ? rawStart + 30 : rawEnd;
            const clippedStart = Math.max(rawStart, dayStart);
            const clippedEnd = Math.min(Math.max(desiredEnd, clippedStart + 10), dayEnd);
            if (clippedStart >= dayEnd || clippedEnd <= dayStart) return null;
            return { event, rawStart, desiredEnd, clippedStart, clippedEnd };
        })
        .filter(Boolean)
        .sort((a, b) => a.rawStart - b.rawStart || a.desiredEnd - b.desiredEnd);

    const result = [];
    let cluster = [];
    let clusterEnd = -1;

    const flush = () => {
        if (!cluster.length) return;
        const columns = [];
        cluster.forEach((item) => {
            let column = columns.findIndex((end) => end <= item.rawStart);
            if (column === -1) {
                column = columns.length;
                columns.push(item.desiredEnd);
            } else {
                columns[column] = item.desiredEnd;
            }
            item.column = column;
        });
        const total = columns.length;
        cluster.forEach((item) => {
            result.push({
                event: item.event,
                style: {
                    top: `${((item.clippedStart - dayStart) / span) * 100}%`,
                    height: `${Math.max(((item.clippedEnd - item.clippedStart) / span) * 100, 4)}%`,
                    left: `calc(${(item.column / total) * 100}% + 2px)`,
                    width: `calc(${100 / total}% - 4px)`,
                },
            });
        });
        cluster = [];
        clusterEnd = -1;
    };

    items.forEach((item) => {
        if (cluster.length && item.rawStart >= clusterEnd) flush();
        cluster.push(item);
        clusterEnd = Math.max(clusterEnd, item.desiredEnd);
    });
    flush();

    return result;
}
