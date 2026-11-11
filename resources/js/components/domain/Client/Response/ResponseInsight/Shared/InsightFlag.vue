<script setup lang="ts">
/**
 * @component InsightFlag
 * @description A neutral chip. A tone puts a small colored dot in front of its text.
 */
import { AppBadge } from '@/components/base/badge';

export type InsightFlagTone = 'error' | 'warning' | 'success' | 'primary' | 'muted';

defineProps<{ tone?: InsightFlagTone | null }>();

const dotClasses: Record<InsightFlagTone, string> = {
    error: 'bg-error',
    warning: 'bg-warning',
    success: 'bg-success',
    primary: 'bg-primary',
    muted: 'bg-primary/40',
};
</script>

<template>
    <AppBadge
        color="neutral"
        variant="subtle"
        size="xs"
        class="gap-tight shrink-0 whitespace-nowrap"
    >
        <span
            v-if="tone"
            class="size-dot-xs shrink-0 rounded-full"
            :class="dotClasses[tone]"
            :data-tone="tone"
            data-testid="insight-flag-dot"
        />
        <slot />
    </AppBadge>
</template>
