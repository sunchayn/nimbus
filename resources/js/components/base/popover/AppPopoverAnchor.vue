<script setup lang="ts">
/**
 * @component AppPopoverAnchor
 * @description An optional element used to anchor the popover.
 */
import type { PopoverAnchorProps } from 'reka-ui';
import { PopoverAnchor } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppPopoverAnchorProps extends PopoverAnchorProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppPopoverAnchorProps>();
</script>

<template>
    <PopoverAnchor data-slot="popover-anchor" v-bind="props">
        <slot />
    </PopoverAnchor>
</template>
