import { CronExpressionParser } from 'cron-parser';

export type TimelineRangeMode = 'day' | 'week';

export interface ScheduleLike {
    id: number | string;
    name?: string;
    cron?: string | null;
    enabled?: boolean | number | string | null;
}

export interface ScheduleOccurrence {
    scheduleId: number | string;
    name: string;
    enabled: boolean;
    at: Date;
}

export interface ExpandOccurrencesOptions {
    schedules: ScheduleLike[];
    rangeStart: Date;
    rangeEnd: Date;
    includeDisabled?: boolean;
    /** Max occurrences per schedule in the window (dense cron guard). */
    maxPerSchedule?: number;
}

const DEFAULT_MAX_PER_SCHEDULE = 500;

export function isScheduleEnabled(enabled: ScheduleLike['enabled']): boolean {
    return enabled === true || enabled === 1 || enabled === '1' || enabled === 'true';
}

/** Local start of calendar day. */
export function startOfDay(date: Date): Date {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return d;
}

/** Local start of next calendar day. */
export function startOfNextDay(date: Date): Date {
    const d = startOfDay(date);
    d.setDate(d.getDate() + 1);
    return d;
}

/** Local Sunday 00:00 of the week containing `date`. */
export function startOfWeek(date: Date): Date {
    const d = startOfDay(date);
    d.setDate(d.getDate() - d.getDay());
    return d;
}

export function getRangeBounds(anchor: Date, mode: TimelineRangeMode): { start: Date; end: Date } {
    switch (mode) {
        case 'day': {
            const start = startOfDay(anchor);
            return { start, end: startOfNextDay(anchor) };
        }
        case 'week': {
            const start = startOfWeek(anchor);
            const end = new Date(start);
            end.setDate(end.getDate() + 7);
            return { start, end };
        }
        default: {
            const _exhaustive: never = mode;
            return _exhaustive;
        }
    }
}

export function shiftAnchor(anchor: Date, mode: TimelineRangeMode, direction: -1 | 1): Date {
    const next = new Date(anchor);
    switch (mode) {
        case 'day':
            next.setDate(next.getDate() + direction);
            return next;
        case 'week':
            next.setDate(next.getDate() + direction * 7);
            return next;
        default: {
            const _exhaustive: never = mode;
            return _exhaustive;
        }
    }
}

export function formatRangeLabel(anchor: Date, mode: TimelineRangeMode): string {
    const { start, end } = getRangeBounds(anchor, mode);
    const dateOpts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric', year: 'numeric' };
    switch (mode) {
        case 'day':
            return start.toLocaleDateString(undefined, {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });
        case 'week': {
            const lastDay = new Date(end);
            lastDay.setDate(lastDay.getDate() - 1);
            return `${start.toLocaleDateString(undefined, dateOpts)} – ${lastDay.toLocaleDateString(undefined, dateOpts)}`;
        }
        default: {
            const _exhaustive: never = mode;
            return _exhaustive;
        }
    }
}

/**
 * Expand schedule crons into occurrence timestamps within [rangeStart, rangeEnd).
 */
export function expandScheduleOccurrences(options: ExpandOccurrencesOptions): {
    occurrences: ScheduleOccurrence[];
    cappedScheduleIds: Array<number | string>;
} {
    const {
        schedules,
        rangeStart,
        rangeEnd,
        includeDisabled = false,
        maxPerSchedule = DEFAULT_MAX_PER_SCHEDULE,
    } = options;

    const occurrences: ScheduleOccurrence[] = [];
    const cappedScheduleIds: Array<number | string> = [];

    if (!(rangeStart instanceof Date) || !(rangeEnd instanceof Date) || rangeEnd <= rangeStart) {
        return { occurrences, cappedScheduleIds };
    }

    // Iterate from just before rangeStart so the first next() can land on/after start.
    const currentDate = new Date(rangeStart.getTime() - 1);

    for (const schedule of schedules) {
        const enabled = isScheduleEnabled(schedule.enabled);
        if (!includeDisabled && !enabled) {
            continue;
        }

        const cron = typeof schedule.cron === 'string' ? schedule.cron.trim() : '';
        if (!cron) {
            continue;
        }

        let expression;
        try {
            expression = CronExpressionParser.parse(cron, {
                currentDate,
                endDate: rangeEnd,
            });
        } catch {
            continue;
        }

        let count = 0;
        let capped = false;
        while (count < maxPerSchedule) {
            let nextDate: Date;
            try {
                nextDate = expression.next().toDate();
            } catch {
                break;
            }

            if (nextDate < rangeStart) {
                continue;
            }
            if (nextDate >= rangeEnd) {
                break;
            }

            occurrences.push({
                scheduleId: schedule.id,
                name: schedule.name?.trim() || `Schedule #${schedule.id}`,
                enabled,
                at: nextDate,
            });
            count += 1;

            if (count >= maxPerSchedule) {
                capped = true;
            }
        }

        if (capped) {
            cappedScheduleIds.push(schedule.id);
        }
    }

    occurrences.sort((a, b) => a.at.getTime() - b.at.getTime());

    return { occurrences, cappedScheduleIds };
}

export interface OccurrenceDayGroup {
    key: string;
    label: string;
    items: ScheduleOccurrence[];
}

export function groupOccurrencesByDay(occurrences: ScheduleOccurrence[]): OccurrenceDayGroup[] {
    const groups = new Map<string, OccurrenceDayGroup>();

    for (const item of occurrences) {
        const dayStart = startOfDay(item.at);
        const key = `${dayStart.getFullYear()}-${dayStart.getMonth() + 1}-${dayStart.getDate()}`;
        let group = groups.get(key);
        if (!group) {
            group = {
                key,
                label: dayStart.toLocaleDateString(undefined, {
                    weekday: 'long',
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                }),
                items: [],
            };
            groups.set(key, group);
        }
        group.items.push(item);
    }

    return Array.from(groups.values());
}

export function formatOccurrenceTime(date: Date): string {
    return date.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function dayKey(date: Date): string {
    const d = startOfDay(date);
    return `${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
}

/** Sunday–Saturday dates for the week containing `anchor`. */
export function getWeekDays(anchor: Date): Date[] {
    const start = startOfWeek(anchor);
    return Array.from({ length: 7 }, (_, i) => {
        const day = new Date(start);
        day.setDate(start.getDate() + i);
        return day;
    });
}

export const HOUR_SLOTS: number[] = Array.from({ length: 24 }, (_, hour) => hour);

export function formatHourLabel(hour: number): string {
    const d = new Date();
    d.setHours(hour, 0, 0, 0);
    return d.toLocaleTimeString(undefined, { hour: 'numeric' });
}

export function formatWeekdayShort(date: Date): string {
    return date.toLocaleDateString(undefined, { weekday: 'short' });
}

export function formatDayNumber(date: Date): string {
    return String(date.getDate());
}

export function isSameDay(a: Date, b: Date): boolean {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

/** Bucket occurrences into dayKey -> hour -> items (sorted within hour). */
export function bucketOccurrencesByDayAndHour(
    occurrences: ScheduleOccurrence[],
): Map<string, Map<number, ScheduleOccurrence[]>> {
    const byDay = new Map<string, Map<number, ScheduleOccurrence[]>>();

    for (const item of occurrences) {
        const key = dayKey(item.at);
        let byHour = byDay.get(key);
        if (!byHour) {
            byHour = new Map();
            byDay.set(key, byHour);
        }
        const hour = item.at.getHours();
        const list = byHour.get(hour) ?? [];
        list.push(item);
        byHour.set(hour, list);
    }

    for (const byHour of byDay.values()) {
        for (const list of byHour.values()) {
            list.sort((a, b) => a.at.getTime() - b.at.getTime());
        }
    }

    return byDay;
}
