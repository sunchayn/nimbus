<script setup lang="ts">
/**
 * @component InsightTimelineBreakdown
 * @description Splits the request time into the time spent on queries and everything else.
 */
import { formatMilliseconds, type Timeline } from '@/utils/insight';

defineProps<{ timeline: Timeline }>();
</script>

<template>
    <div class="gap-tight flex flex-col" data-testid="insight-breakdown">
        <div class="gap-hair h-dot-xs flex" data-testid="insight-breakdown-bar">
            <div
                class="bg-inverted/50 min-w-hit-sm h-full rounded-full"
                :style="{ width: `${timeline.queriesPercent}%` }"
                data-testid="insight-breakdown-queries"
            />
            <div
                class="bg-accented h-full min-w-0 flex-1 rounded-full"
                data-testid="insight-breakdown-other"
            />
        </div>
        <p class="text-muted gap-base flex flex-wrap items-center text-xs">
            <span class="gap-tight flex items-center">
                <span class="bg-inverted/50 size-dot-xs rounded-full" />
                <span>
                    Queries
                    <span class="text-default font-mono tabular-nums">
                        {{ formatMilliseconds(timeline.queriesMs) }}
                    </span>
                    ({{ timeline.queriesPercent }}%)
                </span>
            </span>
            <span class="gap-tight flex items-center">
                <span class="bg-accented size-dot-xs rounded-full" />
                <span>
                    Everything else
                    <span class="text-default font-mono tabular-nums">
                        {{ formatMilliseconds(timeline.otherMs) }}
                    </span>
                </span>
            </span>
        </p>
    </div>
</template>
