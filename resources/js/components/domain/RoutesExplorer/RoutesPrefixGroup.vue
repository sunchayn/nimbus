<script setup lang="ts">
/**
 * @component RoutesPrefixGroup
 * @description A collapsible sidebar group for a route prefix (e.g. "api", "admin").
 * Contains child RoutesResourceGroup components.
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
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { ChevronRight, FolderOpen } from 'lucide-vue-next';
import { inject } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoutesPrefixGroupProps {
    prefix: string;
    routeCount: number;
}

/*
 * Component Setup.
 */

const props = defineProps<AppRoutesPrefixGroupProps>();

/*
 * State.
 */

const isOpen = useStorage(
    singletonPersistenceKey(`routes-explorer-prefix-${props.prefix}-expanded`),
    true,
);

const showingSearchResults = inject('showingSearchResults');
</script>

<template>
    <AppSidebarMenuItem>
        <AppCollapsible
            class="group/collapsible [&[data-state=open]>button>svg:first-child]:rotate-90"
            :default-open="isOpen"
            :open="showingSearchResults ? true : isOpen"
            @update:open="isOpen = $event"
        >
            <AppCollapsibleTrigger as-child>
                <AppSidebarMenuButton
                    class="focus-visible:bg-sidebar-accent data-[active=true]:focus-visible:bg-sidebar-accent font-semibold focus-visible:ring-0"
                >
                    <ChevronRight class="transition-transform" />
                    <FolderOpen />
                    <span
                        class="max-w-[180px] truncate sm:max-w-[220px] md:max-w-[260px] lg:max-w-[300px]"
                    >
                        {{ props.prefix }}
                    </span>
                    <span class="text-muted-foreground ml-auto text-[10px]">
                        {{ routeCount }}
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
