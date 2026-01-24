<script setup lang="ts">
/**
 * @component AppSidebarGroupLabel
 * @description A label for a sidebar group.
 */
import { cn } from '@/utils/ui';
import type { PrimitiveProps } from 'reka-ui';
import { Primitive } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarGroupLabelProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarGroupLabelProps>();
</script>

<template>
    <Primitive
        data-sidebar="group-label"
        :as="as"
        :as-child="asChild"
        :class="
            cn(
                'text-sidebar-foreground/70 ring-sidebar-ring flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium transition-[margin,opa] duration-200 ease-linear outline-none focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0',
                'group-data-[collapsible=icon]:-mt-8 group-data-[collapsible=icon]:opacity-0',
                props.class,
            )
        "
    >
        <slot />
    </Primitive>
</template>
