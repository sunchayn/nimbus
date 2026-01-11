<script setup lang="ts">
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import { AppScrollArea } from '@/components/base/scroll-area';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import type { JSONSchema7 } from 'json-schema';
import { SearchIcon, SearchXIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/*
 * Interfaces.
 */

interface RouteWithError {
    endpoint: string;
    method: string;
    resource: string;
    version: string;
    schema: {
        shape: JSONSchema7;
        extractionErrors: string;
    };
}

/*
 * Props.
 */

interface Props {
    routes: RouteWithError[];
    selectedRoute: RouteWithError | null;
}

const props = defineProps<Props>();

/*
 * Emits.
 */

const emit = defineEmits<{
    'route-selected': [route: RouteWithError];
}>();

/*
 * State.
 */

const searchQuery = ref('');

/*
 * Computed Properties.
 */

const filteredRoutes = computed((): RouteWithError[] => {
    if (!searchQuery.value.trim()) {
        return props.routes;
    }

    // Search both endpoint and resource to help users find routes quickly
    const query = searchQuery.value.toLowerCase();

    return props.routes.filter(
        route =>
            route.endpoint.toLowerCase().includes(query) ||
            route.resource.toLowerCase().includes(query),
    );
});

const hasSearchResults = computed(() => filteredRoutes.value.length > 0);

/*
 * Methods.
 */

const selectRoute = (route: RouteWithError) => {
    emit('route-selected', route);
};

const clearSearch = () => {
    searchQuery.value = '';
};

const isRouteSelected = (route: RouteWithError) => {
    if (!props.selectedRoute) {
        return false;
    }

    return (
        props.selectedRoute.endpoint === route.endpoint &&
        props.selectedRoute.method === route.method
    );
};
</script>

<template>
    <div class="flex h-full flex-col">
        <!-- Routes Header -->
        <div
            class="h-toolbar bg-subtle-background flex flex-shrink-0 items-center justify-between border-b"
        >
            <div class="pl-panel flex items-center">
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Failed Routes
                </span>
                <span class="bg-background ml-2 rounded-full px-2 py-0.5 text-xs">
                    {{ filteredRoutes.length }}
                </span>
            </div>
            <div class="relative h-full w-54 max-w-2/5 border-l">
                <SearchIcon
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 transform"
                />
                <AppInput
                    v-model="searchQuery"
                    placeholder="Search"
                    class="rounded-non h-full border-0 py-0.5 pr-4 pl-10 text-sm shadow-none outline-none focus-visible:ring-0"
                />
            </div>
        </div>

        <!-- Routes List -->
        <AppScrollArea class="min-h-0 flex-1">
            <template v-if="hasSearchResults">
                <div
                    v-for="route in filteredRoutes"
                    :key="`${route.version}-${route.resource}-${route.method}-${route.endpoint}`"
                    class="px-panel dark:hover:bg-accent/20 flex cursor-pointer items-center space-x-3 border-b border-l-2 border-l-transparent py-1 transition-colors odd:bg-white even:bg-gray-50 hover:bg-yellow-50/30 dark:odd:bg-zinc-950 dark:even:bg-zinc-900/30"
                    :class="{
                        '!border-l-black dark:!border-l-zinc-800': isRouteSelected(route),
                    }"
                    @click="selectRoute(route)"
                >
                    <HttpVerbLabel :method="route.method" size="sm" />
                    <div class="min-w-0 flex-1">
                        <div class="text-subtle-foreground truncate font-mono text-sm">
                            {{ route.endpoint }}
                        </div>
                        <div class="truncate text-xs text-gray-500">
                            /{{ route.resource }}
                            <span v-if="route.version !== 'n/a'">
                                • v{{ route.version }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>

            <div v-else class="p-panel">
                <h2 class="text-lg font-medium">No routes match your search criteria</h2>
                <p class="text-subtle-foreground mb-4 text-sm">
                    Try searching for route endpoints or resource names
                </p>
                <AppButton
                    v-if="searchQuery"
                    size="xs"
                    variant="outline"
                    @click="clearSearch"
                >
                    <SearchXIcon class="size-3" />
                    Clear Search
                </AppButton>
            </div>
        </AppScrollArea>
    </div>
</template>
