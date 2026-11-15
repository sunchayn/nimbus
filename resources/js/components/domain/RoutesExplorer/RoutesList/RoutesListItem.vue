<script setup lang="ts">
/**
 * @component RoutesListItem
 * @description An individual list item representing a route definition.
 */
import AppIcon from '@/components/base/icons/AppIcon.vue';
import { AppTooltip } from '@/components/base/tooltip';
import ExplorerItemRow from '@/components/domain/Explorer/ExplorerItemRow.vue';
import { type RouteDefinition } from '@/interfaces/routes/routes';
import { useConfigStore } from '@/stores/core/useConfigStore';
import { useRoutesStore } from '@/stores/routes/useRoutesStore';
import { describeThrottle } from '@/utils/routes';
import { withOccurrenceKeys } from '@/utils/ui';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RoutesListItemProps {
    route: RouteDefinition;
    resource: string;
    isActive?: boolean;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<RoutesListItemProps>(), {
    isActive: false,
});

const routesStore = useRoutesStore();
const configStore = useConfigStore();

/*
 * Computed & Methods.
 */

const endpointsSegments = computed(() => {
    const segments = props.route.shortEndpoint
        .replace(`${props.resource}`, '')
        .split('/');

    if (segments.length > 1 && segments[0] === '') {
        segments.shift();
    }

    const parsedSegments = segments.map(segment => ({
        value: `/${segment}`,
        isRouteVariable: segment.startsWith('{'),
    }));

    return withOccurrenceKeys(parsedSegments, segment => segment.value);
});

const showOperationId = computed(
    () => configStore.showOperationId && props.route.metadata?.operationId,
);

const requiresAuth = computed(() => props.route.metadata?.auth === 'required');

const throttleHint = computed(() => describeThrottle(props.route.metadata?.throttle));

const showUndocumented = computed(() => routesStore.isUndocumented(props.route));

const showMissingImplementation = computed(() =>
    routesStore.isMissingImplementation(props.route),
);
</script>

<template>
    <ExplorerItemRow :method="route.method" :is-active="isActive">
        <template v-if="showOperationId">
            {{ route.metadata?.operationId }}
        </template>
        <template v-for="{ item: segment, key } in endpointsSegments" v-else :key="key">
            <span v-if="!segment.isRouteVariable">{{ segment.value }}</span>
            <span v-else>
                <span class="text-muted">{{ segment.value }}</span>
            </span>
        </template>

        <template #trailing>
            <span
                v-if="requiresAuth"
                class="justify-self-end"
                title="Requires authentication"
                data-testid="route-auth-badge"
            >
                <AppIcon name="i-hugeicons-square-lock-02" class="size-icon-xs" />
            </span>

            <AppTooltip v-if="throttleHint" :text="throttleHint">
                <span class="justify-self-end" data-testid="route-throttle-badge">
                    <AppIcon name="i-hugeicons-timer-01" class="size-icon-xs" />
                </span>
            </AppTooltip>

            <span v-if="showUndocumented" class="justify-self-end">
                <AppIcon name="i-hugeicons-information-circle" class="size-icon-xs" />
            </span>

            <span v-if="showMissingImplementation" class="text-warning justify-self-end">
                <AppIcon name="i-hugeicons-alert-02" class="size-icon-xs" />
            </span>
        </template>
    </ExplorerItemRow>
</template>
