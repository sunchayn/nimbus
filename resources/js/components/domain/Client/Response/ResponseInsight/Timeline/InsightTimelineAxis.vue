<script setup lang="ts">
/**
 * @component InsightTimelineAxis
 * @description The time labels above the rows. It reports the width of its track, so the parent can drop labels that would touch.
 */
import type { TimelineTick } from '@/utils/insight';
import { useElementSize } from '@vueuse/core';
import { ref, watch } from 'vue';

defineProps<{ ticks: TimelineTick[] }>();

const width = defineModel<number>('width', { default: 0 });

const track = ref<HTMLElement | null>(null);

const { width: measured } = useElementSize(track);

watch(measured, value => (width.value = value), { immediate: true });

/** The first and last ticks sit on the edges of the axis, so their labels stay inside it. */
const tickAlignment = (percent: number) =>
    percent <= 0 ? '' : percent >= 100 ? '-translate-x-full' : '-translate-x-1/2';
</script>

<template>
    <div class="gap-base px-tight flex items-end text-xs" aria-hidden="true">
        <span class="w-chip-sm shrink-0" />
        <span class="basis-1/3" />
        <span ref="track" class="h-control-xs relative flex-1">
            <span
                v-for="tick in ticks"
                :key="tick.percent"
                class="text-muted bottom-hair absolute font-mono whitespace-nowrap tabular-nums"
                :class="tickAlignment(tick.percent)"
                :style="{ left: `${tick.percent}%` }"
                data-testid="insight-axis-tick"
            >
                {{ tick.label }}
            </span>
        </span>
        <span class="w-20 shrink-0" />
    </div>
</template>
