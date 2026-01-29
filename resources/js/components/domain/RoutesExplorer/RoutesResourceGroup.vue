<script setup lang="ts">
/**
 * @component RoutesResourceGroup
 * @description A collapsible sidebar group for a specific route resource.
 */
import {
    AppCollapsible,
    AppCollapsibleContent,
    AppCollapsibleTrigger,
} from '@/components/base/collapsible';
import {
    AppSidebarMenuButton,
    AppSidebarMenuItem,
    AppSidebarMenuSub,
} from '@/components/base/sidebar';
import { uniquePersistenceKey } from '@/utils/stores';
import { useStorage } from '@vueuse/core';
import { ChevronRight, Folder } from 'lucide-vue-next';
import { inject } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoutesResourceGroupProps {
    resource: string;
}

/*
 * Component Setup.
 */

const props = defineProps<AppRoutesResourceGroupProps>();

/*
 * State.
 */

const isOpen = useStorage(
    uniquePersistenceKey(`routes-explorer-resource-${props.resource}-expanded`),
    false,
);

const showingSearchResults = inject('showingSearchResults');
</script>

<template>
    <AppSidebarMenuItem>
        <AppCollapsible
            class="group/collapsible [&[data-state=open]>button>svg:first-child]:rotate-90"
            :default-open="isOpen"
            :open="showingSearchResults ? true : undefined"
            @update:open="isOpen = $event"
        >
            <AppCollapsibleTrigger as-child>
                <AppSidebarMenuButton
                    class="focus-visible:bg-sidebar-accent data-[active=true]:focus-visible:bg-sidebar-accent focus-visible:ring-0"
                >
                    <ChevronRight class="transition-transform" />
                    <Folder />
                    <span
                        class="max-w-[180px] truncate sm:max-w-[220px] md:max-w-[260px] lg:max-w-[300px]"
                    >
                        {{ props.resource }}
                    </span>
                </AppSidebarMenuButton>
            </AppCollapsibleTrigger>
            <AppCollapsibleContent class="overflow-hidden">
                <AppSidebarMenuSub>
                    <slot />
                </AppSidebarMenuSub>
            </AppCollapsibleContent>
        </AppCollapsible>
    </AppSidebarMenuItem>
</template>
