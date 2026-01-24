<script setup lang="ts">
/**
 * @component AppSheetTitle
 * @description The main heading for the sheet content.
 */
import { cn } from '@/utils/ui';
import { DialogTitle, type DialogTitleProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSheetTitleProps extends DialogTitleProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetTitleProps>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <DialogTitle
        :class="cn('text-lg font-semibold text-zinc-950 dark:text-zinc-50', props.class)"
        v-bind="delegatedProps"
    >
        <slot />
    </DialogTitle>
</template>
