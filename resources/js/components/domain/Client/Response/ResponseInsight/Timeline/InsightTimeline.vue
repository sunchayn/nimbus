<script setup lang="ts">
/**
 * @component InsightTimeline
 * @description Shows how long the request took and a timeline of its queries and HTTP calls on one time axis.
 * Selecting a row asks the parent to open that query or call.
 */
import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import type { Insight } from '@/interfaces/http';
import {
    buildTimeline,
    fitTicks,
    formatBytes,
    formatMilliseconds,
    type TimelineRow,
} from '@/utils/insight';
import { computed, ref } from 'vue';
import InsightTimelineAxis from './InsightTimelineAxis.vue';
import InsightTimelineBreakdown from './InsightTimelineBreakdown.vue';
import InsightTimelineRow from './InsightTimelineRow.vue';

const props = defineProps<{ insight: Insight }>();

const emit = defineEmits<{
    select: [row: Pick<TimelineRow, 'kind' | 'index'>];
}>();

const timeline = computed(() => buildTimeline(props.insight));

const rowKey = (row: Pick<TimelineRow, 'kind' | 'index'>) => `${row.kind}-${row.index}`;

// Tick labels are fitted to the measured width of the axis, so none of them can touch another.
const axisWidth = ref(0);

const ticks = computed(() => fitTicks(timeline.value?.ticks ?? [], axisWidth.value));
</script>

<template>
    <InsightSection name="Timeline">
        <InsightCard v-if="timeline">
            <template #header>
                <span class="text-muted">Request</span>
                <span
                    class="text-highlighted font-mono tabular-nums"
                    data-testid="insight-stat-duration"
                >
                    {{ formatMilliseconds(timeline.requestMs) }}
                </span>
                <span
                    v-if="insight.peak_memory"
                    class="text-muted ms-auto font-mono tabular-nums"
                    data-testid="insight-stat-memory"
                >
                    {{ formatBytes(insight.peak_memory) }} peak memory
                </span>
            </template>

            <div class="p-base gap-base flex flex-col">
                <InsightTimelineBreakdown :timeline="timeline" />

                <div
                    v-if="timeline.rows.length"
                    class="border-muted pt-base gap-tight flex flex-col border-t"
                >
                    <div class="flex flex-col" data-testid="insight-timeline-axis">
                        <InsightTimelineAxis v-model:width="axisWidth" :ticks="ticks" />

                        <ul class="flex flex-col">
                            <li
                                v-for="row in timeline.rows"
                                :key="rowKey(row)"
                                data-testid="insight-waterfall-item"
                            >
                                <InsightTimelineRow
                                    :row="row"
                                    :ticks="ticks"
                                    @select="emit('select', row)"
                                />
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </InsightCard>

        <p v-if="insight.truncated" class="text-muted text-xs">
            The insight was too large and was trimmed.
        </p>
    </InsightSection>
</template>
