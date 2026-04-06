<script setup lang="ts">
/**
 * @component EnvironmentVariablesEditor
 * @description A wrapper around KeyValueParameters that synchronizes with the environment variables store.
 */
import KeyValueParametersBuilder from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import PanelSubHeader from '@/components/layout/PanelSubHeader/PanelSubHeader.vue';
import type { ParameterContract } from '@/interfaces';
import { useEnvironmentVariablesStore } from '@/stores';

/*
 * Stores & Dependencies.
 */

const environmentVariablesStore = useEnvironmentVariablesStore();

/*
 * Computed & Methods.
 */

const handleVariablesUpdate = (variables: ParameterContract[]) => {
    environmentVariablesStore.updateVariables(variables);
};
</script>

<template>
    <div class="relative flex-1 overflow-hidden rounded border border-b-0">
        <div
            v-if="!environmentVariablesStore.activeCollection"
            class="absolute top-0 left-0 z-10 h-full w-full bg-popover/50 select-none cursor-not-allowed"
        />

        <slot name="header" />

        <PanelSubHeader class="border-y py-1.5">
            Variables defined below can be referenced with
            <span v-pre class="whitespace-nowrap text-violet-600">
                {{ variable_key }}
            </span>
        </PanelSubHeader>

        <KeyValueParametersBuilder
            :model-value="environmentVariablesStore.editableVariables"
            @update:parameters="handleVariablesUpdate"
        />
    </div>
</template>
