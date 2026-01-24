<script setup lang="ts">
import AppPanelStateContainer from '@/components/base/AppPanelStateContainer.vue';
import { AppButton } from '@/components/base/button';
import {
    AppResizableHandle,
    AppResizablePanel,
    AppResizablePanelGroup,
} from '@/components/base/resizable';
import ErrorDetails from '@/components/domain/Status/errors/ErrorDetails.vue';
import ErrorRoutesList from '@/components/domain/Status/errors/ErrorRoutesList.vue';
import RouteStatisticsComponent from '@/components/domain/Status/metrics/RouteStatistics.vue';
import StatusIndicator from '@/components/domain/Status/metrics/StatusIndicator.vue';
import AllRoutesSuccessState from '@/components/domain/Status/states/AllRoutesSuccessState.vue';
import LoadingRoutesState from '@/components/domain/Status/states/LoadingRoutesState.vue';
import NoRoutesState from '@/components/domain/Status/states/NoRoutesState.vue';
import PageLayout from '@/components/layout/PageLayout.vue';
import {
    useRouteStatistics,
    type RouteWithError,
} from '@/composables/data/useRouteStatistics';
import { useResponsiveResizable } from '@/composables/ui/useResponsiveResizable';
import { useRoutesStore } from '@/stores';
import { useTimeAgo } from '@vueuse/core';
import { ClockIcon, RadioIcon, RefreshCwIcon } from 'lucide-vue-next';
import type { TemplateRef } from 'vue';
import { computed, onBeforeMount, ref, useTemplateRef } from 'vue';
import { useRouter } from 'vue-router';

defineOptions({
    name: 'StatusPage',
});

/*
 * Stores.
 */

const routesStore = useRoutesStore();
const router = useRouter();

/*
 * Composables.
 */

const { routeStatistics, displayableRoutesWithErrors } = useRouteStatistics();

/*
 * State.
 */

const lastSyncTime = ref<Date | null>(null);
const selectedRoute = ref<RouteWithError | null>(null);

/*
 * Computed Properties.
 */

const totalRoutes = computed(() => routeStatistics.value.total);
const routesWithErrorsCount = computed(() => routeStatistics.value.withErrors);
const routesWithoutErrors = computed(() => routeStatistics.value.withoutErrors);
const errorRate = computed(() => routeStatistics.value.errorRate);

const timeAgo = computed(() => {
    if (!lastSyncTime.value) {
        return '';
    }

    return useTimeAgo(lastSyncTime.value).value;
});

/*
 * Methods.
 */

const goToMain = () => {
    router.push({ name: 'main' });
};

const refreshRoutes = async () => {
    lastSyncTime.value = new Date();

    // Clear selected route before refreshing to prevent stale data display
    // This ensures users don't see outdated error details while new data loads
    selectedRoute.value = null;

    await routesStore.initializeRoutes();
};

const selectRoute = (route: RouteWithError) => {
    selectedRoute.value = route;
};

onBeforeMount(() => {
    routesStore.initializeRoutes();
    lastSyncTime.value = new Date();
});

const panelsGroupElement: TemplateRef = useTemplateRef('panels-group');
const mainDirection = useResponsiveResizable([600], panelsGroupElement).thresholds[0];
</script>

<template>
    <PageLayout title="Status" :icon="RadioIcon">
        <!-- Header Actions -->
        <template #header-actions>
            <div
                v-if="lastSyncTime"
                class="xs:block text-muted-foreground mr-3 hidden text-xs"
            >
                <ClockIcon class="mr-1 inline size-3" />
                <span>{{ timeAgo }}</span>
            </div>
            <AppButton
                variant="outline"
                size="xs"
                :disabled="routesStore.isLoading"
                @click="refreshRoutes"
            >
                <RefreshCwIcon
                    :class="{ 'animate-spin': routesStore.isLoading }"
                    class="h-3 w-3"
                />
                <span class="xs:inline ml-1 hidden">
                    {{ routesStore.isLoading ? 'Syncing' : 'Refresh' }}
                </span>
            </AppButton>
        </template>

        <!-- Subheader Left -->
        <template #subheader-left>
            <RouteStatisticsComponent
                :total-routes="totalRoutes"
                :routes-with-errors="routesWithErrorsCount"
                :routes-without-errors="routesWithoutErrors"
                :error-rate="errorRate"
            />
        </template>

        <!-- Subheader Right -->
        <template #subheader-right>
            <StatusIndicator
                :routes-with-errors="routesWithErrorsCount"
                :total-routes="totalRoutes"
            />
        </template>

        <!-- Content -->
        <template #content>
            <!-- Routes with Errors -->
            <AppResizablePanelGroup
                v-if="displayableRoutesWithErrors.length > 0 && !routesStore.isLoading"
                ref="panels-group"
                auto-save-id="status-splitter-group"
                :direction="mainDirection"
                class="h-full"
            >
                <!-- Left Panel: Routes List -->
                <AppResizablePanel :min-size="25" :default-size="50">
                    <ErrorRoutesList
                        :routes="displayableRoutesWithErrors"
                        :selected-route="selectedRoute"
                        @route-selected="selectRoute"
                    />
                </AppResizablePanel>

                <AppResizableHandle />

                <!-- Right Panel: Error Details -->
                <AppResizablePanel :min-size="25" :default-size="50">
                    <ErrorDetails :selected-route="selectedRoute" />
                </AppResizablePanel>
            </AppResizablePanelGroup>

            <AppPanelStateContainer v-else class="h-full">
                <AllRoutesSuccessState
                    v-if="!routesStore.isLoading && totalRoutes > 0"
                    :total-routes="totalRoutes"
                    @go-to-main="goToMain"
                />
                <NoRoutesState v-else-if="!routesStore.isLoading && totalRoutes === 0" />
                <LoadingRoutesState v-else />
            </AppPanelStateContainer>
        </template>
    </PageLayout>
</template>
