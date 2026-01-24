<script setup lang="ts">
/**
 * @component AppSeparator
 * @description A visual divider between content sections.
 */
import { cn } from '@/utils/ui';
import { Separator, type SeparatorProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSeparatorProps extends SeparatorProps {
    class?: HTMLAttributes['class'];
    label?: string;
}

/*
 * Component Setup.
 */

const props = defineProps<AppSeparatorProps>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <Separator
        v-bind="delegatedProps"
        :class="
            cn(
                'relative shrink-0 bg-zinc-200 dark:bg-zinc-800',
                props.orientation === 'vertical' ? 'h-full w-px' : 'h-px w-full',
                props.class,
            )
        "
    >
        <span
            v-if="props.label"
            :class="
                cn(
                    'absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center bg-white text-xs text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400',
                    props.orientation === 'vertical'
                        ? 'w-[1px] px-1 py-2'
                        : 'h-[1px] px-2 py-1',
                )
            "
        >
            {{ props.label }}
        </span>
    </Separator>
</template>
