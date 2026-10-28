<script setup lang="ts">
/**
 * @component RequestBuilderMethodSelector
 * @description HTTP method selector for the request builder, with route support awareness.
 */
import { AppSelect } from '@/components/base/select';
import { type RouteDefinition } from '@/interfaces/routes/routes';
import { useRequestStore } from '@/stores';
import { getHttpVerbColorClass, getHttpVerbShortLabel } from '@/utils/http';
import { computed } from 'vue';

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * State.
 */

const availableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

/*
 * Computed & Methods.
 */

const method = computed({
    get: () => requestStore.pendingRequestData?.method?.toUpperCase() ?? 'GET',
    set: (value: string) => requestStore.updateRequestMethod(value.toUpperCase()),
});

const currentRouteSupportedMethods = computed(() => {
    const supportedRoutes = requestStore.pendingRequestData?.supportedRoutes;
    const supportedMethods =
        supportedRoutes?.map((route: RouteDefinition) => route.method) ?? [];

    return availableMethods.filter((m: string) => supportedMethods.includes(m));
});

const currentRouteUnsupportedMethods = computed(() => {
    return availableMethods.filter(
        (m: string) => !currentRouteSupportedMethods.value.includes(m),
    );
});

const items = computed(() => {
    const groups: Array<Array<string | { type: 'label'; label: string }>> = [];

    if (currentRouteSupportedMethods.value.length) {
        groups.push([
            { type: 'label', label: 'Supported' },
            ...currentRouteSupportedMethods.value,
        ]);
    }

    if (currentRouteUnsupportedMethods.value.length) {
        const label = currentRouteSupportedMethods.value.length
            ? [{ type: 'label' as const, label: 'Other' }]
            : [];

        groups.push([...label, ...currentRouteUnsupportedMethods.value]);
    }

    return groups;
});
</script>

<template>
    <div class="border-r">
        <AppSelect
            v-model="method"
            :items="items"
            variant="ghost"
            size="sm"
            placeholder="Select a Method"
            :disabled="!requestStore.pendingRequestData"
            class="h-full w-14 rounded-none font-mono"
            :trailing="false"
            :ui="{
                base: 'font-mono focus-visible:outline-none',
                content: 'min-w-32',
            }"
            :content="{ align: 'start', sideOffset: 6 }"
        >
            <template #default="{ modelValue }">
                <span :class="getHttpVerbColorClass(String(modelValue ?? ''))">
                    {{ getHttpVerbShortLabel(String(modelValue ?? '')) }}
                </span>
            </template>
        </AppSelect>
    </div>
</template>
