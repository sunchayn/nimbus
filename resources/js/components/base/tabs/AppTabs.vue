<script setup lang="ts">
/**
 * @component AppTabs
 * @description Root container for a tabs group, using reka-ui primitives.
 */
import type { TabsRootEmits, TabsRootProps } from 'reka-ui';
import { TabsRoot, useForwardPropsEmits } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppTabsProps extends TabsRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppTabsProps>();
const emits = defineEmits<TabsRootEmits>();

/*
 * Computed & Methods.
 */

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TabsRoot v-bind="forwarded" :unmount-on-hide="false">
        <slot />
    </TabsRoot>
</template>
