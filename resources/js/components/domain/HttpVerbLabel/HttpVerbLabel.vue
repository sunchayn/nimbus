<script setup lang="ts">
/**
 * @component HttpVerbLabel
 * @description A visual indicator for HTTP methods (GET, POST, etc.) with color coding.
 */
import { getHttpVerbColorClass, getHttpVerbShortLabel } from '@/utils/http';
import { cn } from '@/utils/ui';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface HttpVerbLabelProps {
    method: string;
    class?: string;
    size?: 'sm' | 'md' | 'lg';
    variant?: 'solid' | 'outline' | 'soft' | 'subtle';
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<HttpVerbLabelProps>(), {
    class: '',
    size: 'sm',
    variant: 'outline',
});

/*
 * Computed & Methods.
 */

const indicatorColor = computed(() => getHttpVerbColorClass(props.method));

const sizeClasses = computed(() => {
    return {
        sm: 'min-w-12 text-xxs',
        md: 'min-w-15 text-xs',
        lg: 'min-w-18 text-sm',
    }[props.size];
});
</script>

<template>
    <span
        :class="
            cn(
                'inline-block font-mono font-medium',
                sizeClasses,
                indicatorColor,
                props.class,
            )
        "
        data-testid="http-verb-label"
    >
        {{ getHttpVerbShortLabel(method) }}
    </span>
</template>
