<script setup lang="ts">
/**
 * @component AppSelect
 * @description Root container for a select group.
 */
import type { SelectRootEmits, SelectRootProps } from 'reka-ui';
import { SelectRoot, useForwardPropsEmits } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppSelectProps extends SelectRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectProps>();
const emits = defineEmits<SelectRootEmits>();

/*
 * Computed & Methods.
 */

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <SelectRoot v-bind="forwarded">
        <slot />
    </SelectRoot>
</template>
