<script setup lang="ts">
/**
 * @component AppSidebarTrigger
 * @description A button used to toggle the sidebar's open and collapsed states.
 */
import { AppButton } from '@/components/base/button';
import { cn } from '@/utils/ui';
import { PanelLeft } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { useSidebar } from './utils';

/*
 * Types & Interfaces.
 */

export interface AppSidebarTriggerProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSidebarTriggerProps>();

/*
 * Computed & Methods.
 */

const { toggleSidebar } = useSidebar();
</script>

<template>
    <AppButton
        data-sidebar="trigger"
        variant="ghost"
        size="icon"
        :class="cn('size-8', props.class)"
        @click="toggleSidebar"
    >
        <PanelLeft />
        <span class="sr-only">Toggle Sidebar</span>
    </AppButton>
</template>
