<script setup lang="ts">
/**
 * @component HttpVerbLabel
 * @description A visual indicator for HTTP methods (GET, POST, etc.) with color coding.
 */
import { AppBadge } from '@/components/base/badge';
import { cn } from '@/utils/ui';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppHttpVerbLabelProps {
    method: string;
    size?: 'sm' | 'md' | 'lg';
    variant?: 'default' | 'outline';
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppHttpVerbLabelProps>(), {
    size: 'sm',
    variant: 'outline',
});

/*
 * Computed & Methods.
 */

const indicatorColor = computed(() => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const colors: Record<string, string> = {
        POST: 'text-success',
        PUT: 'text-success',
        DELETE: 'text-destructive',
    };
    return colors[props.method] ?? null;
});

const sizeClasses = computed(() => {
    return {
        sm: 'min-w-[50px] text-xxs',
        md: 'min-w-[60px] text-xs',
        lg: 'min-w-[70px] text-sm',
    }[props.size];
});
</script>

<template>
    <AppBadge :variant="variant" :class="cn('justify-center', sizeClasses)">
        <span :class="indicatorColor">{{ method }}</span>
    </AppBadge>
</template>
