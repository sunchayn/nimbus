<script setup lang="ts">
/**
 * @component RouteExplorerVersionSelector
 * @description A dropdown for selecting the API version.
 */
import {
    AppSelect,
    AppSelectContent,
    AppSelectItem,
    AppSelectTrigger,
    AppSelectValue,
} from '@/components/base/select';

/*
 * Types & Interfaces.
 */

export interface AppRouteExplorerVersionSelectorProps {
    versions: string[];
}

/*
 * Component Setup.
 */

defineProps<AppRouteExplorerVersionSelectorProps>();

const model = defineModel<string>();
</script>

<template>
    <AppSelect v-model="model" class="flex items-center">
        <AppSelectTrigger
            class="px-panel w-full rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:outline-none active:ring-0"
        >
            <AppSelectValue placeholder="Select API Version" />
        </AppSelectTrigger>
        <AppSelectContent>
            <AppSelectItem
                v-for="value in versions"
                :key="value"
                :value="value"
                class="text-xs"
            >
                {{ value }}
            </AppSelectItem>
        </AppSelectContent>
    </AppSelect>
</template>
