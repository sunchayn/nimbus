<script setup lang="ts">
/**
 * @component AppTabsTrigger
 * @description The interactive tab header that activates its corresponding content.
 */
import { cn } from '@/utils/ui';
import {
    TabsTrigger,
    useForwardProps,
    type TabsTriggerProps as RekaTabsTriggerProps,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTabsTriggerProps extends RekaTabsTriggerProps {
    label?: string;
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTabsTriggerProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <TabsTrigger
        v-bind="forwardedProps"
        :class="
            cn(
                'inline-flex items-center justify-center rounded-sm px-2.5 py-1 text-sm font-medium whitespace-nowrap ring-offset-white transition-all focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-white data-[state=active]:text-zinc-950 data-[state=active]:shadow-sm dark:ring-offset-zinc-950 dark:focus-visible:ring-zinc-300 dark:data-[state=active]:bg-zinc-950 dark:data-[state=active]:text-zinc-50',
                props.class,
            )
        "
    >
        <span class="truncate">
            <template v-if="label">{{ label }}</template>
            <template v-else>
                <slot />
            </template>
        </span>
    </TabsTrigger>
</template>
