<script setup lang="ts">
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

const props = defineProps({
    resource: {
        type: String,
        required: true,
    },
});

const isOpen = useStorage(
    uniquePersistenceKey(`routes-explorer-resource-${props.resource}-expanded`),
    false,
);
</script>

<template>
    <AppSidebarMenuItem>
        <AppCollapsible
            class="group/collapsible [&[data-state=open]>button>svg:first-child]:rotate-90"
            :default-open="isOpen"
            @update:open="isOpen = $event"
        >
            <AppCollapsibleTrigger as-child>
                <AppSidebarMenuButton>
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
