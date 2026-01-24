<script setup lang="ts">
/**
 * @component PageLayout
 * @description The main structural layout for application pages, including header, subheader, and content area.
 */
import { type Component } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPageLayoutProps {
    title: string;
    icon?: Component;
    scrollable?: boolean;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppPageLayoutProps>(), {
    scrollable: true,
    icon: undefined,
});

defineOptions({
    name: 'PageLayout',
});
</script>

<template>
    <div class="flex h-screen max-h-screen flex-col">
        <!-- Header -->
        <div class="h-toolbar flex items-center overflow-hidden border-b p-0">
            <div class="px-panel flex items-center">
                <component :is="icon" v-if="icon" class="mr-2 size-4" />
                <span class="text-sm font-medium">{{ title }}</span>
            </div>

            <div class="px-panel ml-auto flex items-center">
                <slot name="header-actions" />
            </div>
        </div>

        <!-- Sub Header -->
        <div
            class="px-panel h-sub-toolbar bg-subtle-background flex items-center justify-between border-b"
        >
            <slot name="subheader-left" />
            <slot name="subheader-right" />
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-hidden">
            <slot name="content" />
        </div>
    </div>
</template>
