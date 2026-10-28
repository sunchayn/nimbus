<script setup lang="ts">
/**
 * @component RoutesList
 * @description A list of route folders and their endpoints.
 *              Folders render recursively through RoutesResourceGroup.
 */
import RoutesResourceGroup from '@/components/domain/RoutesExplorer/RoutesResourceGroup.vue';
import { useShowingSearchResults } from '@/components/domain/RoutesExplorer/showingSearchResults';
import { type RoutesFolder } from '@/interfaces/routes/routes';

/*
 * Types & Interfaces.
 */

export interface RoutesListProps {
    routes: RoutesFolder[];
}

/*
 * Component Setup.
 */

defineProps<RoutesListProps>();

/*
 * State.
 */

const showingSearchResults = useShowingSearchResults();
</script>

<template>
    <template v-if="routes && routes.length">
        <RoutesResourceGroup
            v-for="folder in routes"
            :key="folder.path"
            :folder="folder"
        />
    </template>
    <template v-else-if="!showingSearchResults">
        <div class="px-base">
            <p class="mb-base text-xs">
                No routes have been detected. Make sure to check the Wiki in case of
                doubts.
            </p>
        </div>
    </template>
    <template v-else>
        <p class="mb-base px-base text-xs">No routes matching your keywords.</p>
    </template>
</template>
