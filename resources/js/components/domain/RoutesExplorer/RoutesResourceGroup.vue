<script setup lang="ts">
/**
 * @component RoutesResourceGroup
 * @description A collapsible sidebar group for a specific route resource.
 */
import { AppCollapsible } from '@/components/base/collapsible';
import AppIcon from '@/components/base/icons/AppIcon.vue';
import ExplorerRow from '@/components/domain/RoutesExplorer/ExplorerRow.vue';
import { useShowingSearchResults } from '@/components/domain/RoutesExplorer/showingSearchResults';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';

/*
 * Types & Interfaces.
 */

export interface RoutesResourceGroupProps {
    resource: string;
}

/*
 * Component Setup.
 */

const props = defineProps<RoutesResourceGroupProps>();

/*
 * State.
 */

const isOpen = useStorage(
    singletonPersistenceKey(`routes-explorer-resource-${props.resource}-expanded`),
    false,
);

const showingSearchResults = useShowingSearchResults();
</script>

<template>
    <li>
        <AppCollapsible
            class="group/collapsible"
            :default-open="isOpen"
            :open="showingSearchResults ? true : isOpen"
            @update:open="isOpen = $event"
        >
            <ExplorerRow>
                <AppIcon
                    name="i-hugeicons-arrow-right-01"
                    class="-mx-tight shrink-0 transition-transform group-data-[state=open]/collapsible:rotate-90"
                />
                <AppIcon name="i-hugeicons-folder-01" class="shrink-0" />
                <span class="max-w-45 truncate sm:max-w-55 md:max-w-65 lg:max-w-75">
                    {{ props.resource }}
                </span>
            </ExplorerRow>
            <template #content>
                <ul
                    class="border-default ms-3 flex min-w-0 flex-col gap-1 border-s py-1 ps-1"
                >
                    <slot />
                </ul>
            </template>
        </AppCollapsible>
    </li>
</template>
