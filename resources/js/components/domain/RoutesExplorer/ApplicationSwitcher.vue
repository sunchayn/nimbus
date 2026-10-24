<script setup lang="ts">
/**
 * @component ApplicationSwitcher
 * @description A dropdown component for switching between different applications in a multi-app setup.
 */
import { AppSelect } from '@/components/base/select';
import { useConfigStore } from '@/stores';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ApplicationSwitcherProps {}

/*
 * Component Setup.
 */

defineProps<ApplicationSwitcherProps>();

const configStore = useConfigStore();

/*
 * Computed & Methods.
 */

const items = computed(() => [
    { type: 'label' as const, label: 'Applications' },
    ...Object.entries(configStore.applications).map(([key, name]) => ({
        label: name,
        value: key,
    })),
]);

/*
 * Event Handlers.
 */

const handleApplicationChange = (applicationKey: unknown) => {
    if (applicationKey === null) {
        return;
    }

    // We reload the page with the new application query parameter to trigger the backend switch.
    const url = new URL(window.location.href);
    url.searchParams.set('application', String(applicationKey));
    window.location.href = url.toString();
};
</script>

<template>
    <div class="w-full">
        <AppSelect
            :model-value="configStore.activeApplication || ''"
            :items="items"
            variant="outline"
            size="md"
            leading-icon="i-hugeicons-layers-01"
            placeholder="Select an application"
            class="w-full"
            :content="{ alignOffset: 2 }"
            @update:model-value="handleApplicationChange"
        />
    </div>
</template>
