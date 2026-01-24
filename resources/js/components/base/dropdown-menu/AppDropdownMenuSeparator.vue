<script setup lang="ts">
/**
 * @component AppDropdownMenuSeparator
 * @description A visual divider for dropdown menu items.
 */
import { cn } from '@/utils/ui';
import { DropdownMenuSeparator, type DropdownMenuSeparatorProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuSeparatorProps extends DropdownMenuSeparatorProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuSeparatorProps>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <DropdownMenuSeparator
        v-bind="delegatedProps"
        :class="cn('-mx-1 my-1 h-px bg-zinc-100 dark:bg-zinc-800', props.class)"
    />
</template>
