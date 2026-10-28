<script setup lang="ts">
/**
 * @component EnvironmentCollectionList
 * @description Renders the sidemenu list of environment collections with active state management.
 */
import { AppBadge } from '@/components/base/badge';
import { AppIcon } from '@/components/base/icons';
import AppRoundIndicator from '@/components/base/round-indicator/AppRoundIndicator.vue';
import { useEnvironmentVariablesStore } from '@/stores';

/*
 * Stores & Dependencies.
 */

const environmentVariablesStore = useEnvironmentVariablesStore();

/*
 * Computed & Methods.
 */

const handleCollectionSelect = (collectionId: string) => {
    environmentVariablesStore.select(collectionId);
};
</script>

<template>
    <div class="gap-base flex w-full max-w-xs flex-col">
        <div
            v-if="!environmentVariablesStore.hasCollections"
            type="button"
            class="hover:bg-muted w-full cursor-pointer rounded-sm border text-left text-sm"
            @click="environmentVariablesStore.addCollection"
        >
            <div
                class="p-panel h-toolbar gap-base flex items-center"
                data-testid="empty-collection-placeholder"
            >
                <AppIcon name="i-hugeicons-plus-sign" class="size-icon-xs" />
                Add your first Collection
            </div>
        </div>

        <div
            v-for="collection in environmentVariablesStore.collections"
            :key="collection.id"
            type="button"
            class="p-panel hover:bg-muted w-full cursor-pointer rounded-sm border text-left text-sm"
            data-testid="collection-item"
            :class="{
                'bg-muted':
                    environmentVariablesStore.activeCollectionId === collection.id,
            }"
            @click="handleCollectionSelect(collection.id)"
        >
            <div class="gap-base flex items-center">
                <div class="gap-tight flex-1 leading-tight">
                    <div class="mb-0 font-medium">{{ collection.name }}</div>
                    <span class="text-xs">
                        {{ collection.variables.length }} variables
                    </span>
                </div>
                <div class="gap-base flex items-center">
                    <AppBadge
                        v-if="
                            environmentVariablesStore.activeCollectionId === collection.id
                        "
                        variant="outline"
                        color="success"
                        data-testid="collection-active-badge"
                    >
                        Active
                    </AppBadge>
                    <AppRoundIndicator
                        v-if="
                            environmentVariablesStore.activeCollectionId === collection.id
                        "
                        class="text-success"
                    />
                    <AppRoundIndicator v-else class="text-muted" />
                </div>
            </div>
        </div>
    </div>
</template>
