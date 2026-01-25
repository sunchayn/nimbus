<script setup lang="ts">
/**
 * @component AppSidebarInput
 * @description A specialized input component for use within the sidebar.
 */
import { AppInput, type InputVariants } from '@/components/base/input';
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarInputProps {
    class?: HTMLAttributes['class'];
    variant?: InputVariants['variant'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarInputProps>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <AppInput
        data-sidebar="input"
        v-bind="delegatedProps"
        :class="
            cn(
                'h-8 w-full bg-white shadow-none focus-visible:ring-0 dark:bg-zinc-950',
                props.class,
            )
        "
    >
        <slot />
    </AppInput>
</template>
