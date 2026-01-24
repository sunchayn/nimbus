<script setup lang="ts">
/**
 * @component AppSidebarMenuButton
 * @description The main interactive button for sidebar menu items, with tooltip support.
 */
import {
    AppTooltip,
    AppTooltipContent,
    AppTooltipTrigger,
} from '@/components/base/tooltip';
import { type Component, computed } from 'vue';
import SidebarMenuButtonChild, {
    type AppSidebarMenuButtonChildProps,
} from './AppSidebarMenuButtonChild.vue';
import { useSidebar } from './utils';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface AppSidebarMenuButtonComponentProps extends AppSidebarMenuButtonChildProps {
    tooltip?: string | Component;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSidebarMenuButtonComponentProps>(), {
    as: 'button',
    variant: 'default',
    size: 'default',
    tooltip: '',
});

/*
 * Computed & Methods.
 */

const { state } = useSidebar();

const delegatedProps = computed(() => {
    /* eslint-disable @typescript-eslint/no-unused-vars */
    const { tooltip, ...delegated } = props;
    /* eslint-enable @typescript-eslint/no-unused-vars */

    return delegated;
});
</script>

<template>
    <SidebarMenuButtonChild v-if="!tooltip" v-bind="{ ...delegatedProps, ...$attrs }">
        <slot />
    </SidebarMenuButtonChild>

    <AppTooltip v-else>
        <AppTooltipTrigger as-child>
            <SidebarMenuButtonChild v-bind="{ ...delegatedProps, ...$attrs }">
                <slot />
            </SidebarMenuButtonChild>
        </AppTooltipTrigger>
        <AppTooltipContent side="right" align="center" :hidden="state !== 'collapsed'">
            <template v-if="typeof tooltip === 'string'">
                {{ tooltip }}
            </template>
            <component :is="tooltip" v-else />
        </AppTooltipContent>
    </AppTooltip>
</template>
