<script setup lang="ts">
/**
 * @component AppCommandSeparator
 * @description A visual divider for separating command groups or items.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { SeparatorProps } from 'reka-ui';
import { Separator } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppCommandSeparatorProps extends SeparatorProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCommandSeparatorProps>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <Separator
        data-slot="command-separator"
        v-bind="delegatedProps"
        :class="cn('bg-border -mx-1 h-px', props.class)"
    >
        <slot />
    </Separator>
</template>
