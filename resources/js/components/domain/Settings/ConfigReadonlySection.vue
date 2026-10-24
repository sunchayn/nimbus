<script setup lang="ts">
/**
 * @component ConfigReadonlySection
 * @description A read-only display of the current application configuration.
 */
import { AppIcon } from '@/components/base/icons';
import { AppLabel } from '@/components/base/label';
import { useConfigStore } from '@/stores';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ConfigReadonlySectionProps {}

/*
 * Component Setup.
 */

defineProps<ConfigReadonlySectionProps>();

/*
 * Stores.
 */

const configStore = useConfigStore();

/*
 * Computed & Methods.
 */

const configData = computed(() => ({
    routePrefix: 'api',
    isVersioned: configStore.isVersioned,
    baseUrl: configStore.apiUrl,
    basePath: configStore.appBasePath,
    globalHeaders: configStore.headers,
}));
</script>

<template>
    <div class="gap-loose flex flex-col">
        <!-- Route Configuration -->
        <div class="gap-base sm:gap-loose grid sm:grid-cols-3">
            <div class="gap-tight flex flex-col">
                <div class="gap-base flex items-center">
                    <AppIcon name="i-hugeicons-route-01" class="size-icon-md" />
                    <h3 class="text-sm font-medium">Route Configuration</h3>
                </div>
                <p class="text-muted text-xs">
                    API routing settings including prefix and versioning configuration.
                </p>
            </div>
            <div
                class="border-default gap-base p-card grid rounded-md border sm:col-span-2 sm:grid-cols-2"
            >
                <div class="gap-tight flex flex-col">
                    <AppLabel class="text-muted text-xs">Route Prefix</AppLabel>
                    <div
                        class="bg-muted h-control-md px-control-md flex items-center rounded-md font-mono text-sm"
                    >
                        {{ configData.routePrefix }}
                    </div>
                </div>

                <div class="gap-tight flex flex-col">
                    <AppLabel class="text-muted text-xs">Versioned</AppLabel>
                    <div
                        class="bg-muted h-control-md gap-base px-control-md flex items-center rounded-md text-sm"
                    >
                        <div
                            :class="configData.isVersioned ? 'bg-success' : 'bg-accented'"
                            class="size-2 rounded-full"
                        ></div>
                        <span>
                            {{ configData.isVersioned ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Configuration -->
        <div class="gap-base sm:gap-loose grid sm:grid-cols-3">
            <div class="gap-tight flex flex-col">
                <div class="gap-base flex items-center">
                    <AppIcon name="i-hugeicons-globe-02" class="size-icon-md" />
                    <h3 class="text-sm font-medium">API Configuration</h3>
                </div>
                <p class="text-muted text-xs">
                    Base URL and path settings for API endpoints.
                </p>
            </div>
            <div
                class="border-default gap-base p-card grid rounded-md border sm:col-span-2 sm:grid-cols-2"
            >
                <div class="gap-tight flex flex-col">
                    <AppLabel class="text-muted text-xs">Base URL</AppLabel>
                    <div
                        class="bg-muted h-control-md px-control-md flex items-center rounded-md font-mono text-sm"
                    >
                        {{ configData.baseUrl }}
                    </div>
                </div>

                <div class="gap-tight flex flex-col">
                    <AppLabel class="text-muted text-xs">Base Path</AppLabel>
                    <div
                        class="bg-muted h-control-md px-control-md flex items-center rounded-md font-mono text-sm"
                    >
                        {{ configData.basePath || 'None' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Headers -->
        <div class="gap-base sm:gap-loose grid sm:grid-cols-3">
            <div class="gap-tight flex flex-col">
                <div class="gap-base flex items-center">
                    <AppIcon name="i-hugeicons-shield-01" class="size-icon-md" />
                    <h3 class="text-sm font-medium">Global Headers</h3>
                </div>
                <p class="text-muted text-xs">
                    Headers automatically included with all API requests.
                </p>
            </div>
            <div
                class="border-default gap-base p-card flex flex-col rounded-md border sm:col-span-2"
            >
                <template v-if="configData.globalHeaders.length > 0">
                    <div
                        v-for="header in configData.globalHeaders"
                        :key="header.header"
                        class="bg-muted h-control-md px-control-md flex items-center justify-between rounded-md"
                    >
                        <span class="font-mono text-sm">{{ header.header }}</span>
                        <div class="gap-base flex items-center">
                            <div
                                :class="
                                    header.type === 'generator'
                                        ? 'bg-info'
                                        : 'bg-accented'
                                "
                                class="size-2 rounded-full"
                            ></div>
                            <span class="text-muted text-xs">
                                {{ header.type === 'generator' ? 'Generated' : 'Static' }}
                            </span>
                        </div>
                    </div>
                </template>

                <div v-else class="text-muted text-center text-sm italic">
                    No global headers configured
                </div>
            </div>
        </div>
    </div>
</template>
