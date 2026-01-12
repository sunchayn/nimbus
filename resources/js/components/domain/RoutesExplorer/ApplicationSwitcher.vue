<script setup lang="ts">
import {
    AppSelect,
    AppSelectContent,
    AppSelectGroup,
    AppSelectItem,
    AppSelectLabel,
    AppSelectTrigger,
    AppSelectValue,
} from '@/components/base/select';
import { useConfigStore } from '@/stores';
import { LayersIcon } from 'lucide-vue-next';
import { AcceptableValue } from 'reka-ui';

const configStore = useConfigStore();

const handleApplicationChange = (applicationKey: AcceptableValue) => {
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
            @update:model-value="handleApplicationChange"
        >
            <AppSelectTrigger
                class="px-panel w-full border-none text-xs shadow-none focus:ring-0 active:ring-0"
            >
                <div class="flex items-center gap-2 overflow-hidden">
                    <LayersIcon class="text-muted-foreground size-3.5 shrink-0" />
                    <AppSelectValue
                        placeholder="Select an application"
                        class="truncate"
                    />
                </div>
            </AppSelectTrigger>
            <AppSelectContent>
                <AppSelectGroup>
                    <AppSelectLabel>Available Applications</AppSelectLabel>
                    <AppSelectItem
                        v-for="(name, key) in configStore.applications"
                        :key="key"
                        :value="key"
                        class="text-xs"
                    >
                        {{ name }}
                    </AppSelectItem>
                </AppSelectGroup>
            </AppSelectContent>
        </AppSelect>
    </div>
</template>
