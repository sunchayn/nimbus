<script setup lang="ts">
/**
 * @component AppSidebar
 * @description Collapsible sidebar. The app uses it as a fixed icon rail.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { SidebarProps, SidebarSlots } from '@nuxt/ui/components/Sidebar.vue';
import USidebar from '@nuxt/ui/components/Sidebar.vue';

/*
 * Types & Interfaces.
 */

export interface AppSidebarProps extends SidebarProps {
    open?: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarProps>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

defineSlots<SidebarSlots>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <USidebar v-bind="forwarded" @update:open="value => emit('update:open', value)">
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </USidebar>
</template>
