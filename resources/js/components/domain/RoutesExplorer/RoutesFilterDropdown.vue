<script setup lang="ts">
/**
 * @component RoutesFilterDropdown
 * @description A searchable dropdown to filter routes by method, access and middleware.
 */
import { AppButton } from '@/components/base/button';
import { AppCommand } from '@/components/base/command';
import { AppPopover } from '@/components/base/popover';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import {
    emptyRouteFilters,
    hasActiveRouteFilters,
    routeMatchesFilters,
    type RouteFilters,
} from '@/utils/routes';
import { computed, shallowRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RoutesFilterDropdownProps {
    routes: RouteDefinition[];
    methods: string[];
    middleware: string[];
    disabled?: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<RoutesFilterDropdownProps>();

const filters = defineModel<RouteFilters>({ required: true });

/*
 * State.
 */

const open = shallowRef(false);

/*
 * Computed & Methods.
 */

const isActive = computed(() => hasActiveRouteFilters(filters.value));

const activeCount = computed(
    () =>
        filters.value.methods.length +
        filters.value.middleware.length +
        (filters.value.requiresAuth ? 1 : 0),
);

const countRoutes = (partial: Partial<RouteFilters>) =>
    props.routes.filter(route =>
        routeMatchesFilters(route, { ...emptyRouteFilters(), ...partial }),
    ).length;

const toggle = (list: string[], value: string) =>
    list.includes(value) ? list.filter(item => item !== value) : [...list, value];

// Selecting an item keeps the dropdown open, so several filters can be picked at once.
const keepOpen = (event: Event) => event.preventDefault();

const groups = computed(() => {
    const result = [
        {
            id: 'methods',
            label: 'Method',
            items: props.methods.map(method => ({
                label: method,
                count: countRoutes({ methods: [method] }),
                icon: filters.value.methods.includes(method)
                    ? 'i-hugeicons-tick-02'
                    : undefined,
                onSelect: (event: Event) => {
                    keepOpen(event);
                    filters.value = {
                        ...filters.value,
                        methods: toggle(filters.value.methods, method),
                    };
                },
            })),
        },
        {
            id: 'access',
            label: 'Access',
            items: [
                {
                    label: 'Requires auth',
                    count: countRoutes({ requiresAuth: true }),
                    icon: filters.value.requiresAuth ? 'i-hugeicons-tick-02' : undefined,
                    onSelect: (event: Event) => {
                        keepOpen(event);
                        filters.value = {
                            ...filters.value,
                            requiresAuth: !filters.value.requiresAuth,
                        };
                    },
                },
            ],
        },
        {
            id: 'middleware',
            label: 'Middleware',
            items: props.middleware.map(name => ({
                label: name.split('\\').pop() ?? name,
                count: countRoutes({ middleware: [name] }),
                icon: filters.value.middleware.includes(name)
                    ? 'i-hugeicons-tick-02'
                    : undefined,
                onSelect: (event: Event) => {
                    keepOpen(event);
                    filters.value = {
                        ...filters.value,
                        middleware: toggle(filters.value.middleware, name),
                    };
                },
            })),
        },
    ];

    return result.filter(group => group.items.length > 0);
});

const chipClass =
    'bg-elevated border-default text-toned h-icon-lg min-w-icon-lg px-tight inline-flex items-center justify-center rounded-sm border text-xs leading-none tabular-nums';

const clear = () => {
    filters.value = emptyRouteFilters();
};
</script>

<template>
    <AppPopover v-model:open="open" :content="{ align: 'end' }">
        <AppButton
            icon="i-hugeicons-filter"
            :color="isActive ? 'primary' : 'neutral'"
            variant="outline"
            size="md"
            :disabled="disabled"
            aria-label="Filter routes"
            data-testid="routes-filter-toggle"
        >
            <template v-if="isActive" #default>
                <span :class="chipClass" data-testid="routes-filter-active-count">
                    {{ activeCount }}
                </span>
            </template>
        </AppButton>

        <template #content>
            <div class="w-64" data-testid="routes-filters">
                <AppCommand
                    :groups="groups"
                    placeholder="Filter by method or middleware..."
                    :ui="{ content: 'max-h-64 overflow-y-auto', empty: 'text-start' }"
                >
                    <template #empty>No filter matches your search.</template>
                    <template #item-trailing="{ item }">
                        <span
                            v-if="typeof item.count === 'number'"
                            :class="chipClass"
                            data-testid="routes-filter-count"
                        >
                            {{ item.count }}
                        </span>
                    </template>
                    <template v-if="isActive" #footer>
                        <div
                            class="p-tight flex items-center justify-between text-xs"
                            data-testid="routes-filters-footer"
                        >
                            <span class="text-muted">
                                {{
                                    `${activeCount} filter${activeCount === 1 ? '' : 's'} applied`
                                }}
                            </span>
                            <AppButton
                                label="Clear"
                                size="xs"
                                variant="ghost"
                                color="neutral"
                                data-testid="routes-filter-clear"
                                @click="clear"
                            />
                        </div>
                    </template>
                </AppCommand>
            </div>
        </template>
    </AppPopover>
</template>
