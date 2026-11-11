<script setup lang="ts">
/**
 * @component InsightCard
 * @description A white card with a header and an optional body, which can collapse when asked.
 */
import { AppCollapsible } from '@/components/base/collapsible';
import AppIcon from '@/components/base/icons/AppIcon.vue';
import { computed, useSlots } from 'vue';

export interface InsightCardProps {
    collapsible?: boolean;
    /** Draws a ring around the card, to point at it. */
    highlighted?: boolean;
}

const props = defineProps<InsightCardProps>();

const open = defineModel<boolean>('open', { default: false });

const slots = useSlots();

const frameClass = computed(() => [
    'border-muted bg-default @container relative overflow-hidden rounded-md border',
    'after:ring-focus after:ring-primary after:z-raised after:pointer-events-none after:absolute after:inset-0 after:rounded-md after:ring-inset',
    'after:duration-base motion-safe:after:transition-opacity',
    props.highlighted ? 'after:opacity-100' : 'after:opacity-0',
]);
</script>

<template>
    <AppCollapsible
        v-if="collapsible"
        v-model:open="open"
        :class="frameClass"
        :data-highlighted="highlighted || undefined"
    >
        <button
            type="button"
            class="group/insight-card px-panel py-base gap-base hover:bg-elevated/50 flex w-full items-center text-left text-xs"
            data-testid="insight-card-trigger"
        >
            <AppIcon
                name="i-hugeicons-arrow-right-01"
                class="text-muted size-icon-xs duration-base shrink-0 transition-transform group-data-[state=open]/insight-card:rotate-90 motion-reduce:transition-none"
            />
            <span class="gap-base flex min-w-0 flex-1 items-center">
                <slot name="header" :open="open" />
            </span>
        </button>

        <template #content>
            <div class="border-muted border-t">
                <slot />
            </div>
        </template>
    </AppCollapsible>

    <div v-else :class="frameClass" :data-highlighted="highlighted || undefined">
        <div
            class="px-panel py-base gap-base flex items-center text-xs"
            :class="slots.default ? 'border-muted border-b' : ''"
        >
            <slot name="header" :open="open" />
        </div>
        <div v-if="slots.default">
            <slot />
        </div>
    </div>
</template>
