<script setup lang="ts">
/**
 * @component ConfigReadonlySection
 * @description A read-only display of the current application configuration.
 */
import { AppLabel } from "@/components/base/label";
import { useConfigStore } from "@/stores";
import { GlobeIcon, RouteIcon, ShieldIcon } from "lucide-vue-next";
import { computed } from "vue";

/*
 * Types & Interfaces.
 */

export interface AppConfigReadonlySectionProps {}

/*
 * Component Setup.
 */

defineProps<AppConfigReadonlySectionProps>();

/*
 * Stores.
 */

const configStore = useConfigStore();

/*
 * Computed & Methods.
 */

const configData = computed(() => ({
    routePrefix: "api",
    isVersioned: configStore.isVersioned,
    baseUrl: configStore.apiUrl,
    basePath: configStore.appBasePath,
    globalHeaders: configStore.headers,
}));
</script>

<template>
    <div class="space-y-6">
        <!-- Route Configuration -->
        <div class="grid gap-2 sm:grid-cols-12 sm:gap-8">
            <div class="col-span-4">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <RouteIcon class="h-4 w-4" />
                        <h3 class="text-sm font-medium">Route Configuration</h3>
                    </div>
                    <p class="text-subtle-foreground text-xs">
                        API routing settings including prefix and versioning
                        configuration.
                    </p>
                </div>
            </div>
            <div class="col-span-8">
                <div class="rounded-lg border p-3.5">
                    <div class="grid gap-2 sm:grid-cols-2 sm:gap-6">
                        <div class="space-y-2">
                            <AppLabel class="text-subtle-foreground text-xs">
                                Route Prefix
                            </AppLabel>
                            <div
                                class="bg-subtle flex h-9 items-center rounded px-3 font-mono text-sm"
                            >
                                {{ configData.routePrefix }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <AppLabel class="text-subtle-foreground text-xs">
                                Versioned
                            </AppLabel>
                            <div
                                class="bg-subtle flex h-9 items-center rounded px-3 text-sm"
                            >
                                <div class="flex items-center space-x-2">
                                    <div
                                        :class="
                                            configData.isVersioned
                                                ? 'bg-success'
                                                : 'bg-subtle-foreground'
                                        "
                                        class="h-2 w-2 rounded-full"
                                    ></div>
                                    <span>
                                        {{
                                            configData.isVersioned
                                                ? "Enabled"
                                                : "Disabled"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Configuration -->
        <div class="grid gap-2 sm:grid-cols-12 sm:gap-8">
            <div class="col-span-4">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <GlobeIcon class="h-4 w-4" />
                        <h3 class="text-sm font-medium">API Configuration</h3>
                    </div>
                    <p class="text-subtle-foreground text-xs">
                        Base URL and path settings for API endpoints.
                    </p>
                </div>
            </div>
            <div class="col-span-8">
                <div class="rounded-lg border p-3.5">
                    <div class="grid gap-2 sm:grid-cols-2 sm:gap-6">
                        <div class="space-y-2">
                            <AppLabel class="text-subtle-foreground text-xs">
                                Base URL
                            </AppLabel>
                            <div
                                class="flex h-9 items-center rounded bg-subtle px-3 font-mono text-sm"
                            >
                                {{ configData.baseUrl }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <AppLabel class="text-subtle-foreground text-xs">
                                Base Path
                            </AppLabel>
                            <div
                                class="flex h-9 items-center rounded bg-subtle px-3 font-mono text-sm"
                            >
                                {{ configData.basePath || "None" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Headers -->
        <div class="grid gap-2 sm:grid-cols-12 sm:gap-8">
            <div class="col-span-4">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <ShieldIcon class="h-4 w-4" />
                        <h3 class="text-sm font-medium">Global Headers</h3>
                    </div>
                    <p class="text-subtle-foreground text-xs">
                        Headers automatically included with all API requests.
                    </p>
                </div>
            </div>
            <div class="col-span-8">
                <div class="rounded-lg border p-3.5">
                    <div
                        v-if="configData.globalHeaders.length > 0"
                        class="space-y-3"
                    >
                        <div
                            v-for="header in configData.globalHeaders"
                            :key="header.header"
                            class="bg-subtle flex items-center justify-between rounded p-3.5"
                        >
                            <span class="font-mono text-sm">{{
                                header.header
                            }}</span>
                            <div class="flex items-center space-x-2">
                                <div
                                    :class="
                                        header.type === 'generator'
                                            ? 'bg-info'
                                            : 'bg-subtle-foreground'
                                    "
                                    class="h-2 w-2 rounded-full"
                                ></div>
                                <span class="text-subtle-foreground text-xs">
                                    {{
                                        header.type === "generator"
                                            ? "Generated"
                                            : "Static"
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="text-subtle-foreground py-4 text-center text-sm italic"
                    >
                        No global headers configured
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
