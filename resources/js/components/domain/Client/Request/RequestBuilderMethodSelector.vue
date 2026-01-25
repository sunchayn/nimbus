<script setup lang="ts">
/**
 * @component RequestBuilderMethodSelector
 * @description HTTP method selector for the request builder, with route support awareness.
 */
import {
    AppSelect,
    AppSelectContent,
    AppSelectGroup,
    AppSelectItem,
    AppSelectLabel,
    AppSelectSeparator,
    AppSelectTrigger,
    AppSelectValue,
} from '@/components/base/select';
import { type RouteDefinition } from '@/interfaces/routes/routes';
import { useRequestStore } from '@/stores';
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
</script>

<template>
    <AppSelect v-model="method">
        <AppSelectTrigger
            variant="toolbar"
            class="h-full w-[95px] border-r pr-1.5 pl-5 text-xs"
        >
            <AppSelectValue :placeholder="method ? '' : 'Select a Method'">
                {{ method || 'Select a Method' }}
            </AppSelectValue>
        </AppSelectTrigger>
        <AppSelectContent :align-offset="2">
            <AppSelectGroup v-if="currentRouteSupportedMethods.length">
                <AppSelectLabel>Supported</AppSelectLabel>
                <AppSelectItem
                    v-for="supportedMethod in currentRouteSupportedMethods"
                    :key="supportedMethod"
                    :value="supportedMethod"
                >
                    {{ supportedMethod }}
                </AppSelectItem>
            </AppSelectGroup>
            <AppSelectGroup v-if="currentRouteUnsupportedMethods.length !== 0">
                <template v-if="currentRouteSupportedMethods.length">
                    <AppSelectSeparator />
                    <AppSelectLabel>Other</AppSelectLabel>
                </template>
                <AppSelectItem
                    v-for="unsupportedMethod in currentRouteUnsupportedMethods"
                    :key="unsupportedMethod"
                    :value="unsupportedMethod"
                >
                    {{ unsupportedMethod }}
                </AppSelectItem>
            </AppSelectGroup>
        </AppSelectContent>
    </AppSelect>
</template>
