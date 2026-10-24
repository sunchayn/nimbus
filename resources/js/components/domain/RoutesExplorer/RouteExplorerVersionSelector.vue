<script setup lang="ts">
/**
 * @component RouteExplorerVersionSelector
 * @description A dropdown for selecting the API version.
 */
import { AppSelect } from '@/components/base/select';

/*
 * Types & Interfaces.
 */

export interface RouteExplorerVersionSelectorProps {
    versions: string[];
}

/*
 * Component Setup.
 */

defineProps<RouteExplorerVersionSelectorProps>();

const model = defineModel<string>();
</script>

<template>
    <AppSelect
        v-model="model"
        :items="versions"
        variant="outline"
        size="md"
        placeholder="Select API Version"
        class="w-full"
    />
</template>
