<script setup lang="ts">
/**
 * @component InsightTimelineRow
 * @description One query or HTTP call, with its bar placed on the time axis. A click asks the parent to open it.
 */
import AppIcon from '@/components/base/icons/AppIcon.vue';
import { formatMilliseconds, type TimelineRow, type TimelineTick } from '@/utils/insight';
import { computed } from 'vue';

const props = defineProps<{ row: TimelineRow; ticks: TimelineTick[] }>();

defineEmits<{ select: [] }>();

// Calls are a lighter tone than queries, and only a slow query has a color.
const barColor = computed(() => {
    const { kind, isSlow, depth } = props.row;

    if (kind === 'http') {
        return 'bg-accented';
    }

    if (isSlow) {
        return depth ? 'bg-error/50' : 'bg-error';
    }

    return depth ? 'bg-primary/40' : 'bg-primary';
});

const label = computed(() =>
    props.row.kind === 'http'
        ? `HTTP call, ${props.row.label}, ${formatMilliseconds(props.row.ms)}. Show it in HTTP.`
        : `Query, ${props.row.label}, ${formatMilliseconds(props.row.ms)}. Show it in Queries.`,
);

const timeColor = computed(() => {
    if (props.row.depth) {
        return 'text-dimmed';
    }

    return props.row.isSlow ? 'text-error' : 'text-muted';
});
</script>

<template>
    <button
        type="button"
        class="gap-base px-tight hover:bg-elevated/50 duration-fast flex w-full cursor-pointer items-stretch rounded-sm text-left text-xs motion-safe:transition-colors"
        :aria-label="label"
        :title="row.title"
        data-testid="insight-waterfall-row"
        @click="$emit('select')"
    >
        <span
            class="text-dimmed py-tight w-chip-sm flex shrink-0 items-center justify-end font-mono tabular-nums"
        >
            <AppIcon
                v-if="row.kind === 'http'"
                name="i-hugeicons-globe-02"
                class="size-icon-xs"
            />
            <AppIcon
                v-else-if="!row.depth"
                name="i-hugeicons-database"
                class="size-icon-xs"
                data-testid="insight-waterfall-db-icon"
            />
        </span>
        <span
            class="relative flex basis-1/3 items-stretch overflow-hidden"
            :class="row.depth ? 'ps-base' : ''"
            :data-depth="row.depth"
            data-testid="insight-waterfall-cell"
        >
            <span
                class="py-tight relative flex min-w-0 flex-1 items-center"
                :class="
                    row.depth
                        ? 'border-accented ps-loose before:bg-accented before:w-base before:h-line border-s before:absolute before:start-0 before:top-1/2'
                        : ''
                "
            >
                <span
                    class="truncate font-mono"
                    :class="row.depth ? 'text-muted' : 'text-default'"
                    data-testid="insight-waterfall-label"
                >
                    {{ row.label }}
                </span>
            </span>
        </span>
        <span class="relative flex-1">
            <span
                v-for="tick in ticks"
                :key="tick.percent"
                class="bg-accented/60 w-line absolute inset-y-0"
                :style="{ left: `${tick.percent}%` }"
            />
            <span
                class="h-dot-xs min-w-hit-sm absolute top-1/2 -translate-y-1/2 rounded-full"
                :class="barColor"
                :style="{
                    left: `${row.offsetPercent}%`,
                    width: `${row.widthPercent}%`,
                }"
                data-testid="insight-waterfall-bar"
            />
        </span>
        <span
            class="py-tight w-20 shrink-0 text-right font-mono tabular-nums"
            :class="timeColor"
        >
            {{ formatMilliseconds(row.ms) }}
        </span>
    </button>
</template>
