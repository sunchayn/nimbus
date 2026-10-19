<script setup lang="ts">
/**
 * @component RoutesList
 * @description A list of grouped route resources and their endpoints.
 *              Builds a nested folder tree from the flat resource paths so
 *              resources like "creator/auth/password" render as nested
 *              folders (Postman-style), rendered recursively by
 *              RoutesResourceGroup.
 */
import RoutesResourceGroup from '@/components/domain/RoutesExplorer/RoutesResourceGroup.vue';
import { type RoutesGroup } from '@/interfaces/routes/routes';
import { buildResourceTree } from '@/utils/routes/resourceTree';
import { computed, inject } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoutesListProps {
    routes: RoutesGroup[];
}

/*
 * Component Setup.
 */

const props = defineProps<AppRoutesListProps>();

/*
 * Computed & Methods.
 */

const resourceTree = computed(() => buildResourceTree(props.routes));

const showingSearchResults = inject('showingSearchResults');
</script>

<template>
    <template v-if="routes && routes.length">
        <RoutesResourceGroup
            v-for="node in resourceTree"
            :key="node.fullPath"
            :node="node"
        />
    </template>
    <template v-else-if="!showingSearchResults">
        <div class="px-2">
            <p class="mb-2 text-xs">
                No routes have been detected. Make sure to check the Wiki in case of
                doubts.
            </p>
        </div>
    </template>
    <template v-else>
        <p class="mb-2 px-2 text-xs">No routes matching your keywords.</p>
    </template>
</template>
