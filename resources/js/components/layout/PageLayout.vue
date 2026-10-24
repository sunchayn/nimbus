<script setup lang="ts">
/**
 * @component PageLayout
 * @description The main structural layout for application pages, including header, subheader, and content area.
 */
import { AppIcon } from '@/components/base/icons';

/*
 * Types & Interfaces.
 */

export interface PageLayoutProps {
    title: string;
    icon?: string;
    scrollable?: boolean;
}

/*
 * Component Setup.
 */

withDefaults(defineProps<PageLayoutProps>(), {
    scrollable: true,
    icon: undefined,
});

defineOptions({
    name: 'PageLayout',
});
</script>

<template>
    <div class="p-base h-full">
        <div
            class="bg-default border-default rounded-pane flex h-full flex-col overflow-hidden border"
        >
            <!-- Header -->
            <div class="h-toolbar flex items-center overflow-hidden border-b p-0">
                <div class="px-panel gap-base flex items-center">
                    <AppIcon v-if="icon" :name="icon" class="size-icon-md" />
                    <span class="text-sm font-medium">{{ title }}</span>
                </div>

                <div class="px-panel ms-auto flex items-center">
                    <slot name="header-actions" />
                </div>
            </div>

            <!-- Sub Header -->
            <div
                class="px-panel h-sub-toolbar bg-muted flex items-center justify-between border-b"
            >
                <slot name="subheader-left" />
                <slot name="subheader-right" />
            </div>

            <!-- Content Area -->
            <div class="flex-1 overflow-hidden">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>
