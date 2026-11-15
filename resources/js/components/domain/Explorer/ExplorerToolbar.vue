<script setup lang="ts">
/**
 * @component ExplorerToolbar
 * @description The search box and the filters that apply to the routes and the workspace.
 */
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import RoutesFilterDropdown from '@/components/domain/RoutesExplorer/RoutesFilterDropdown.vue';
import type { RouteDefinition } from '@/interfaces/routes';
import type { RouteFilters } from '@/utils/routes';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ExplorerToolbarProps {
    routes: RouteDefinition[];
    methods: string[];
    middleware: string[];
    disabled?: boolean;
    loading?: boolean;
    // Unix seconds of the routes snapshot, the refresh button is hidden without it.
    snapshotAt?: number | null;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<ExplorerToolbarProps>(), {
    disabled: false,
    loading: false,
    snapshotAt: null,
});

defineEmits<{
    refresh: [];
}>();

const search = defineModel<string>('search', { required: true });
const filters = defineModel<RouteFilters>('filters', { required: true });

/*
 * Computed & Methods.
 */

const snapshotTitle = computed(() =>
    props.snapshotAt
        ? `Refresh routes. Last updated ${new Date(props.snapshotAt * 1000).toLocaleString()}.`
        : 'Refresh routes',
);
</script>

<template>
    <div class="gap-tight flex shrink-0 items-center">
        <AppInput
            v-model="search"
            size="md"
            variant="outline"
            leading-icon="i-hugeicons-search-01"
            placeholder="Type to search..."
            :disabled="disabled"
            class="min-w-0 flex-1"
        />
        <RoutesFilterDropdown
            v-model="filters"
            :routes="routes"
            :methods="methods"
            :middleware="middleware"
            :disabled="disabled"
        />
        <AppButton
            v-if="snapshotAt"
            icon="i-hugeicons-reload"
            color="neutral"
            variant="outline"
            size="md"
            square
            :loading="loading"
            :title="snapshotTitle"
            aria-label="Refresh routes"
            data-testid="routes-refresh"
            @click="$emit('refresh')"
        />
    </div>
</template>
