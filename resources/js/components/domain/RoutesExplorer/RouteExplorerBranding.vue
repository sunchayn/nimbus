<script setup lang="ts">
/**
 * @component RouteExplorerBranding
 * @description Branding text/logo for the route explorer. Shows the configured
 * application name (defaults to the host app's APP_NAME via nimbus.app_name).
 */
import { useConfigStore } from '@/stores';

/*
 * Types & Interfaces.
 */

export interface AppRouteExplorerBrandingProps {}

/*
 * Component Setup.
 */

defineProps<AppRouteExplorerBrandingProps>();

const configStore = useConfigStore();
</script>

<template>
    <div class="text-foreground flex-1 text-xl font-medium">
        {{ configStore.appName }}
    </div>
</template>
