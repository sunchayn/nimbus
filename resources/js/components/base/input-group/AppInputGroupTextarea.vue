<script setup lang="ts">
/**
 * @component AppInputGroupTextarea
 * @description A specialized textarea for use inside an input group.
 */
import { AppTextarea } from '@/components/base/textarea';
import { cn } from '@/utils';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppInputGroupTextareaProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppInputGroupTextareaProps>();
</script>

<template>
    <AppTextarea
        data-slot="input-group-control"
        :class="
            cn(
                'flex-1 resize-none rounded-none border-0 bg-transparent py-3 shadow-none ring-offset-transparent focus-visible:ring-0 focus-visible:ring-transparent dark:bg-transparent',
                props.class,
            )
        "
    />
</template>
