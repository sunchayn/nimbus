<script setup lang="ts">
/**
 * @component InsightSection
 * @description The body of one insight tab, with an empty note and an optional note about dropped items.
 */
export interface InsightSectionProps {
    name: string;
    /** Shown in place of the content when there is nothing to list. */
    empty?: string;
    dropped?: number;
}

defineProps<InsightSectionProps>();
</script>

<template>
    <section
        :data-testid="`insight-${name.toLowerCase().replaceAll(' ', '-')}`"
        class="gap-base flex flex-col text-sm"
    >
        <p v-if="empty" class="text-muted py-loose text-center text-xs">
            {{ empty }}
        </p>
        <slot v-else />
        <p
            v-if="dropped"
            class="text-muted px-hair text-xs"
            data-testid="insight-dropped"
        >
            {{ dropped }} more not shown.
        </p>
    </section>
</template>
