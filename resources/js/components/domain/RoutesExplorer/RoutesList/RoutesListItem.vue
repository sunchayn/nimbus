<script setup lang="ts">
/**
 * @component RoutesListItem
 * @description An individual list item representing a route definition.
 */
import AppIcon from '@/components/base/icons/AppIcon.vue';
import { AppTooltip } from '@/components/base/tooltip';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import ExplorerRow from '@/components/domain/RoutesExplorer/ExplorerRow.vue';
import { type RouteDefinition } from '@/interfaces/routes/routes';
import { useConfigStore, useRoutesStore } from '@/stores';
import { describeThrottle } from '@/utils/routes';
import { withOccurrenceKeys } from '@/utils/ui';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RoutesListItemProps {
    route: RouteDefinition;
    resource: string;
    isActive: boolean;
    onClick?: () => void;
}

export interface RoutesListItemEmits {
    (e: 'click'): void;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<RoutesListItemProps>(), {
    isActive: false,
    onClick: () => {},
});

const emit = defineEmits<RoutesListItemEmits>();

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

const handleClick = () => {
    if (props.onClick) {
        props.onClick();
    }

    emit('click');
};
</script>

<template>
    <ExplorerRow :is-active="isActive" @click="handleClick">
        <HttpVerbLabel class="min-w-8" :method="route.method" />
        <span class="flex-1 whitespace-nowrap">
            <template v-if="showOperationId">
                {{ route.metadata?.operationId }}
            </template>
            <template
                v-for="{ item: segment, key } in endpointsSegments"
                v-else
                :key="key"
            >
                <span v-if="!segment.isRouteVariable">{{ segment.value }}</span>
                <span v-else>
                    <span class="text-muted">{{ segment.value }}</span>
                </span>
            </template>
        </span>

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
    </ExplorerRow>
</template>
