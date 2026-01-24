<script setup lang="ts">
/**
 * @component RouteExplorerHeader
 * @description The header containing branding for the routes explorer sidebar.
 */
import { AppSidebarHeader } from '@/components/base/sidebar';
import RouteExplorerBranding from '@/components/domain/RoutesExplorer/RouteExplorerBranding.vue';

/*
 * Types & Interfaces.
 */

export interface AppRouteExplorerHeaderProps {}

/*
 * Component Setup.
 */

defineProps<AppRouteExplorerHeaderProps>();
</script>

<template>
    <AppSidebarHeader
        class="h-toolbar flex flex-row items-center justify-between border-b"
    >
        <RouteExplorerBranding />
    </AppSidebarHeader>
</template>
