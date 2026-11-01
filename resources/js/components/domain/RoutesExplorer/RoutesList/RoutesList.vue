<script setup lang="ts">
/**
 * @component RoutesList
 * @description A list of route folders and their endpoints.
 *              Folders render recursively through RoutesResourceGroup.
 */
import RoutesResourceGroup from '@/components/domain/RoutesExplorer/RoutesResourceGroup.vue';
import { useShowingSearchResults } from '@/components/domain/RoutesExplorer/showingSearchResults';
import { USER_GUIDE_LINK } from '@/config';
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
            <p class="text-sm">
                No routes have been detected.
            </p>
            <p class="text-xs">
                Check the <a :href="USER_GUIDE_LINK" class="underline" target="_blank">documentation</a> for more insights.
            </p>
        </div>
    </template>
    <template v-else>
        <p class="px-base text-xs">No routes matching your keywords.</p>
    </template>
</template>
