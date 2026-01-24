<script setup lang="ts">
/**
 * @component AppCommandList
 * @description Scrollable container for command items.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { ListboxContentProps } from 'reka-ui';
import { ListboxContent, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCommandListProps extends ListboxContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCommandListProps>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <ListboxContent
        data-slot="command-list"
        v-bind="forwarded"
        :class="
            cn('max-h-[300px] scroll-py-1 overflow-x-hidden overflow-y-auto', props.class)
        "
    >
        <div role="presentation">
            <slot />
        </div>
    </ListboxContent>
</template>
