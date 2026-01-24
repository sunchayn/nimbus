<script setup lang="ts">
/**
 * @component AppCallout
 * @description A callout component for displaying important information, warnings, or success messages.
 */
import { cn } from '@/utils/ui';
import {
    AlertTriangleIcon,
    CheckCircleIcon,
    InfoIcon,
    XCircleIcon,
} from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCalloutProps {
    class?: HTMLAttributes['class'];
    variant?: 'default' | 'info' | 'success' | 'warning' | 'destructive';
    title?: string;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppCalloutProps>(), {
    variant: 'default',
    class: '',
    title: '',
});

/*
 * Computed & Methods.
 */

const iconMap = {
    default: InfoIcon,
    info: InfoIcon,
    success: CheckCircleIcon,
    warning: AlertTriangleIcon,
    destructive: XCircleIcon,
};

const variantClasses = {
    default:
        'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-100',
    info: 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-100',
    success:
        'bg-green-50 border-green-200 text-green-900 dark:bg-green-950 dark:border-green-800 dark:text-green-100',
    warning:
        'bg-yellow-50 border-yellow-200 text-yellow-900 dark:bg-yellow-950 dark:border-yellow-800 dark:text-yellow-100',
    destructive:
        'bg-red-50 border-red-200 text-red-900 dark:bg-red-950 dark:border-red-800 dark:text-red-100',
};

const iconClasses = {
    default: 'text-blue-600 dark:text-blue-400',
    info: 'text-blue-600 dark:text-blue-400',
    success: 'text-green-600 dark:text-green-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    destructive: 'text-red-600 dark:text-red-400',
};

const Icon = iconMap[props.variant];
</script>

<template>
    <div :class="cn('rounded-sm border p-4', variantClasses[props.variant], props.class)">
        <div class="flex items-start gap-3">
            <Icon
                :class="cn('mt-0.5 size-4 flex-shrink-0', iconClasses[props.variant])"
            />
            <div class="flex-1 space-y-1">
                <h4 v-if="props.title" class="text-sm font-semibold">
                    {{ props.title }}
                </h4>
                <div class="text-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
