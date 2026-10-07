<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AlertModal from '@/components/AlertModal.vue';
import SwitchField from '@/components/fields/SwitchField.vue';
import {
    HOUR_SLOTS,
    bucketOccurrencesByDayAndHour,
    dayKey,
    expandScheduleOccurrences,
    formatDayNumber,
    formatHourLabel,
    formatOccurrenceTime,
    formatRangeLabel,
    formatWeekdayShort,
    getRangeBounds,
    getWeekDays,
    isSameDay,
    shiftAnchor,
    startOfDay,
    type ScheduleLike,
    type ScheduleOccurrence,
    type TimelineRangeMode,
} from '@/lib/scheduleOccurrences';

const props = defineProps<{
    isOpen: boolean;
    schedules: ScheduleLike[];
}>();

const emit = defineEmits<{
    close: [];
}>();

/** Max chips shown in a calendar hour cell before "+N more". */
const MAX_VISIBLE_PER_HOUR = 3;

const mode = ref<TimelineRangeMode>('week');
const anchor = ref(new Date());
const includeDisabled = ref(false);
const today = ref(new Date());
/** Expanded "+N more" hour cells, keyed by `${dayKey}-${hour}`. */
const expandedCells = ref<Record<string, boolean>>({});

const includeDisabledSwitchOptions = [
    { label: 'Include disabled', value: false },
    { label: 'Include disabled', value: true },
];

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            anchor.value = new Date();
            today.value = new Date();
            mode.value = 'week';
            includeDisabled.value = false;
            expandedCells.value = {};
        }
    },
);

watch([mode, anchor], () => {
    expandedCells.value = {};
});

const rangeLabel = computed(() => formatRangeLabel(anchor.value, mode.value));

const modalWidth = computed(() => (mode.value === 'week' ? 'sm:max-w-6xl' : 'sm:max-w-3xl'));

const expansion = computed(() => {
    const { start, end } = getRangeBounds(anchor.value, mode.value);
    return expandScheduleOccurrences({
        schedules: props.schedules,
        rangeStart: start,
        rangeEnd: end,
        includeDisabled: includeDisabled.value,
    });
});

const hourBuckets = computed(() => bucketOccurrencesByDayAndHour(expansion.value.occurrences));

const weekDays = computed(() => getWeekDays(anchor.value));

const totalCount = computed(() => expansion.value.occurrences.length);

const wasCapped = computed(() => expansion.value.cappedScheduleIds.length > 0);

function cellKey(day: Date, hour: number): string {
    return `${dayKey(day)}-${hour}`;
}

function isCellExpanded(day: Date, hour: number): boolean {
    return !!expandedCells.value[cellKey(day, hour)];
}

function toggleCellExpanded(day: Date, hour: number, event?: Event) {
    event?.stopPropagation();
    event?.preventDefault();
    const key = cellKey(day, hour);
    expandedCells.value = {
        ...expandedCells.value,
        [key]: !expandedCells.value[key],
    };
}

function itemsFor(day: Date, hour: number): ScheduleOccurrence[] {
    return hourBuckets.value.get(dayKey(day))?.get(hour) ?? [];
}

function visibleItems(day: Date, hour: number): ScheduleOccurrence[] {
    const items = itemsFor(day, hour);
    if (isCellExpanded(day, hour)) {
        return items;
    }
    return items.slice(0, MAX_VISIBLE_PER_HOUR);
}

function overflowCount(day: Date, hour: number): number {
    if (isCellExpanded(day, hour)) {
        return 0;
    }
    return Math.max(0, itemsFor(day, hour).length - MAX_VISIBLE_PER_HOUR);
}

function onIncludeDisabledChange(value: boolean | string) {
    includeDisabled.value = value === true || value === 'true';
}

function setMode(next: TimelineRangeMode) {
    mode.value = next;
}

function openDay(day: Date) {
    anchor.value = startOfDay(day);
    mode.value = 'day';
}

function goPrev() {
    anchor.value = shiftAnchor(anchor.value, mode.value, -1);
}

function goNext() {
    anchor.value = shiftAnchor(anchor.value, mode.value, 1);
}

function goToday() {
    anchor.value = new Date();
    today.value = new Date();
}

function close() {
    emit('close');
}
</script>

<template>
    <AlertModal
        :isOpen="isOpen"
        title="Schedule Timeline"
        :width="modalWidth"
        @close="close"
    >
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="inline-flex rounded-lg border border-border p-0.5">
                    <button
                        type="button"
                        class="px-3 py-1.5 text-sm rounded-md transition-colors"
                        :class="mode === 'day'
                            ? 'bg-blue-600 text-white'
                            : 'text-muted-foreground hover:bg-muted'"
                        @click="setMode('day')"
                    >
                        Day
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-sm rounded-md transition-colors"
                        :class="mode === 'week'
                            ? 'bg-blue-600 text-white'
                            : 'text-muted-foreground hover:bg-muted'"
                        @click="setMode('week')"
                    >
                        Week
                    </button>
                </div>

                <div class="inline-flex items-center gap-1">
                    <button
                        type="button"
                        class="px-2.5 py-1.5 text-sm rounded-md border border-border hover:bg-muted transition-colors"
                        aria-label="Previous"
                        @click="goPrev"
                    >
                        ‹
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 text-sm rounded-md border border-border hover:bg-muted transition-colors"
                        @click="goToday"
                    >
                        Today
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1.5 text-sm rounded-md border border-border hover:bg-muted transition-colors"
                        aria-label="Next"
                        @click="goNext"
                    >
                        ›
                    </button>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-foreground">{{ rangeLabel }}</p>
                <SwitchField
                    name="includeDisabled"
                    :value="includeDisabled"
                    :options="includeDisabledSwitchOptions"
                    :inFieldset="true"
                    @update:value="onIncludeDisabledChange"
                />
            </div>

            <p v-if="wasCapped" class="text-xs text-amber-700 dark:text-amber-400">
                Some schedules fire very often; showing a capped list of runs in this range.
            </p>

            <!-- Day calendar -->
            <div
                v-if="mode === 'day'"
                class="max-h-[60vh] overflow-auto rounded-lg border border-border"
            >
                <div
                    v-if="totalCount === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    No scheduled runs in this range.
                </div>
                <div v-else class="min-w-[280px]">
                    <div
                        v-for="hour in HOUR_SLOTS"
                        :key="hour"
                        class="grid grid-cols-[4.5rem_1fr] border-b border-border last:border-b-0 min-h-[3.25rem]"
                    >
                        <div class="px-2 py-2 text-xs tabular-nums text-muted-foreground border-r border-border">
                            {{ formatHourLabel(hour) }}
                        </div>
                        <div class="flex flex-col gap-1 p-1.5">
                            <div
                                v-for="(item, index) in visibleItems(anchor, hour)"
                                :key="`${item.scheduleId}-${item.at.getTime()}-${index}`"
                                class="rounded px-2 py-1 text-xs bg-slate-100 text-slate-900 border border-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600 truncate"
                                :class="item.enabled ? '' : 'opacity-60'"
                                :title="`${formatOccurrenceTime(item.at)} — ${item.name}`"
                            >
                                <span class="tabular-nums font-medium text-slate-700 dark:text-slate-300">{{ formatOccurrenceTime(item.at) }}</span>
                                {{ ' ' }}{{ item.name }}
                            </div>
                            <button
                                v-if="overflowCount(anchor, hour) > 0"
                                type="button"
                                class="px-2 text-left text-[11px] text-blue-700 dark:text-blue-300 hover:underline"
                                @click="toggleCellExpanded(anchor, hour, $event)"
                            >
                                +{{ overflowCount(anchor, hour) }} more
                            </button>
                            <button
                                v-else-if="isCellExpanded(anchor, hour) && itemsFor(anchor, hour).length > MAX_VISIBLE_PER_HOUR"
                                type="button"
                                class="px-2 text-left text-[11px] text-muted-foreground hover:underline"
                                @click="toggleCellExpanded(anchor, hour, $event)"
                            >
                                Show less
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Week calendar -->
            <div
                v-else
                class="max-h-[60vh] overflow-auto rounded-lg border border-border"
            >
                <div class="min-w-[720px]">
                    <div class="sticky top-0 z-10 grid grid-cols-[4.5rem_repeat(7,minmax(0,1fr))] border-b border-border bg-muted/95 backdrop-blur">
                        <div class="border-r border-border" />
                        <button
                            v-for="day in weekDays"
                            :key="dayKey(day)"
                            type="button"
                            class="px-1 py-2 text-center border-r border-border last:border-r-0 hover:bg-muted/80 transition-colors cursor-pointer"
                            :class="isSameDay(day, today) ? 'bg-blue-50/80 dark:bg-blue-950/30' : ''"
                            :title="`View ${formatWeekdayShort(day)} ${formatDayNumber(day)}`"
                            @click="openDay(day)"
                        >
                            <div class="text-[11px] uppercase tracking-wide text-muted-foreground">
                                {{ formatWeekdayShort(day) }}
                            </div>
                            <div
                                class="text-sm font-medium"
                                :class="isSameDay(day, today) ? 'text-blue-700 dark:text-blue-300' : 'text-foreground'"
                            >
                                {{ formatDayNumber(day) }}
                            </div>
                        </button>
                    </div>

                    <div
                        v-if="totalCount === 0"
                        class="px-4 py-10 text-center text-sm text-muted-foreground border-b border-border"
                    >
                        No scheduled runs in this week. Click a day to inspect it.
                    </div>

                    <div
                        v-for="hour in HOUR_SLOTS"
                        v-show="totalCount > 0"
                        :key="hour"
                        class="grid grid-cols-[4.5rem_repeat(7,minmax(0,1fr))] border-b border-border last:border-b-0 min-h-[3.25rem]"
                    >
                        <div class="px-2 py-2 text-xs tabular-nums text-muted-foreground border-r border-border">
                            {{ formatHourLabel(hour) }}
                        </div>
                        <div
                            v-for="day in weekDays"
                            :key="`${dayKey(day)}-${hour}`"
                            role="button"
                            tabindex="0"
                            class="border-r border-border last:border-r-0 p-1 flex flex-col gap-0.5 text-left hover:bg-muted/60 transition-colors cursor-pointer"
                            :class="isSameDay(day, today) ? 'bg-blue-50/40 dark:bg-blue-950/20' : ''"
                            :title="`View ${formatWeekdayShort(day)} ${formatDayNumber(day)}`"
                            @click="openDay(day)"
                            @keydown.enter.prevent="openDay(day)"
                        >
                            <div
                                v-for="(item, index) in visibleItems(day, hour)"
                                :key="`${item.scheduleId}-${item.at.getTime()}-${index}`"
                                class="rounded px-1.5 py-0.5 text-[11px] leading-snug bg-slate-100 text-slate-900 border border-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600 truncate"
                                :class="item.enabled ? '' : 'opacity-60'"
                                :title="`${formatOccurrenceTime(item.at)} — ${item.name}`"
                            >
                                {{ item.name }}
                            </div>
                            <button
                                v-if="overflowCount(day, hour) > 0"
                                type="button"
                                class="px-1 text-left text-[10px] text-blue-700 dark:text-blue-300 hover:underline"
                                @click="toggleCellExpanded(day, hour, $event)"
                            >
                                +{{ overflowCount(day, hour) }} more
                            </button>
                            <button
                                v-else-if="isCellExpanded(day, hour) && itemsFor(day, hour).length > MAX_VISIBLE_PER_HOUR"
                                type="button"
                                class="px-1 text-left text-[10px] text-muted-foreground hover:underline"
                                @click="toggleCellExpanded(day, hour, $event)"
                            >
                                Show less
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-muted-foreground">
                    {{ totalCount }} run{{ totalCount === 1 ? '' : 's' }}
                    <span v-if="mode === 'week'" class="text-muted-foreground/80"> · click a day for details</span>
                </p>
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors"
                    @click="close"
                >
                    Close
                </button>
            </div>
        </div>
    </AlertModal>
</template>
