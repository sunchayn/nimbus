<script setup lang="ts">
/**
 * @component CommandPalette
 * @description A global palette to search routes, open tabs, and environments, and to run app actions.
 */
import { AppCommand, type AppCommandGroup } from '@/components/base/command';
import { AppDialog } from '@/components/base/dialog';
import { useAppToast } from '@/components/base/toast';
import { useEnvironmentSwitch } from '@/composables/environment/useEnvironmentSwitch';
import { useCommandPalette } from '@/composables/ui/useCommandPalette';
import { useConfirmationAction } from '@/composables/ui/useConfirmationAction';
import {
    useConfigStore,
    useEnvironmentVariablesStore,
    useRequestsHistoryStore,
    useRequestStore,
    useRoutesStore,
    useSettingsStore,
    useTabsStore,
} from '@/stores';
import { switchApplication } from '@/utils/applications';
import { generateCurlCommand } from '@/utils/request';
import { listRoutes } from '@/utils/routes';
import { useClipboard } from '@vueuse/core';
import { computed, shallowRef, watch } from 'vue';
import { useRouter } from 'vue-router';

/*
 * Stores & dependencies.
 */

const router = useRouter();
const toast = useAppToast();
const { copy } = useClipboard();
const switchEnvironment = useEnvironmentSwitch();
const confirmation = useConfirmationAction();
const { isPaletteOpen, paletteMode, closePalette, isToolboxOpen } = useCommandPalette();

const routesStore = useRoutesStore();
const tabsStore = useTabsStore();
const requestStore = useRequestStore();
const configStore = useConfigStore();
const settingsStore = useSettingsStore();
const historyStore = useRequestsHistoryStore();
const environmentStore = useEnvironmentVariablesStore();

/*
 * State.
 */

const search = shallowRef('');

let previouslyFocused: HTMLElement | null = null;

watch(isPaletteOpen, open => {
    if (open) {
        previouslyFocused =
            document.activeElement instanceof HTMLElement ? document.activeElement : null;
    } else {
        search.value = '';
        confirmation.cancelAll();
    }
});

const restoreFocus = (event: Event) => {
    event.preventDefault();
    previouslyFocused?.focus();
    previouslyFocused = null;
};

/*
 * Items.
 */

const goToMain = () => {
    if (router.currentRoute.value.name !== 'main') {
        router.push({ name: 'main' });
    }
};

const run = (action: () => void) => () => {
    closePalette();
    action();
};

const routeItems = computed(() =>
    listRoutes(routesStore.routes).map(({ route, siblings }) => ({
        label: `${route.method.toUpperCase()} ${route.shortEndpoint || route.endpoint}`,
        icon: 'i-hugeicons-route-01',
        onSelect: run(() => {
            goToMain();
            requestStore.initializeRequest(route, siblings);
        }),
    })),
);

const tabItems = computed(() =>
    tabsStore.tabs.map(tab => ({
        label: `${tab.method.toUpperCase()} ${tab.customTitle ?? tab.title}`,
        icon: 'i-hugeicons-layout-top',
        onSelect: run(() => {
            goToMain();
            tabsStore.setActiveTab(tab.id);
        }),
    })),
);

const environmentItems = computed(() =>
    environmentStore.enabledEnvironments.map(environment => ({
        label: `Switch environment to ${environment.name}`,
        icon: 'i-hugeicons-all-bookmark',
        onSelect: run(() => switchEnvironment(environment.id)),
    })),
);

const applicationItems = computed(() =>
    Object.entries(configStore.applications)
        .filter(
            ([key]) =>
                Object.keys(configStore.applications).length > 1 &&
                key !== configStore.activeApplication,
        )
        .map(([key, name]) => ({
            label: `Switch application to ${name}`,
            icon: 'i-hugeicons-layers-01',
            onSelect: run(() => switchApplication(key)),
        })),
);

const copyAsCode = () => {
    const request = requestStore.pendingRequestData;

    if (!request) {
        return;
    }

    copy(
        generateCurlCommand(request, environmentStore.baseUrl, environmentStore.resolve)
            .command,
    );
    toast.success('Copied as cURL');
};

const actionItems = computed(() => {
    const hasRequest = requestStore.hasActiveRequest;

    return [
        {
            label: 'New tab',
            icon: 'i-hugeicons-plus-sign',
            onSelect: () => {
                paletteMode.value = 'routes';
                search.value = '';
            },
        },
        ...(tabsStore.activeTabId
            ? [
                  {
                      label: 'Close tab',
                      icon: 'i-hugeicons-cancel-01',
                      onSelect: run(() =>
                          tabsStore.closeTab(tabsStore.activeTabId ?? ''),
                      ),
                  },
              ]
            : []),
        ...(hasRequest
            ? [
                  {
                      label: 'Toggle transaction mode',
                      icon: 'i-hugeicons-database',
                      onSelect: run(() =>
                          requestStore.updateTransactionMode(
                              !requestStore.pendingRequestData?.transactionMode,
                          ),
                      ),
                  },
                  {
                      label: 'Copy as code',
                      icon: 'i-hugeicons-source-code',
                      onSelect: run(copyAsCode),
                  },
              ]
            : []),
        {
            label: 'Toggle theme',
            icon: 'i-hugeicons-moon-02',
            onSelect: run(() =>
                settingsStore.updatePreference(
                    'theme',
                    settingsStore.preferences.theme === 'dark' ? 'light' : 'dark',
                ),
            ),
        },
        {
            label: 'Open toolbox',
            icon: 'i-hugeicons-tools',
            onSelect: run(() => {
                isToolboxOpen.value = true;
            }),
        },
        {
            label: 'Open settings',
            icon: 'i-hugeicons-settings-01',
            onSelect: run(() => {
                router.push({ name: 'settings' });
            }),
        },
        {
            label: confirmation.isConfirming('clear-history')
                ? 'Select again to clear the history'
                : 'Clear history',
            icon: 'i-hugeicons-delete-02',
            onSelect: () =>
                confirmation.trigger(() => {
                    closePalette();
                    historyStore.clearLogs();
                    toast.success('History cleared');
                }, 'clear-history'),
        },
    ];
});

const groups = computed<AppCommandGroup[]>(() => {
    const routes = { id: 'routes', label: 'Routes', items: routeItems.value };

    if (paletteMode.value === 'routes') {
        return [routes];
    }

    return [
        { id: 'actions', label: 'Actions', items: actionItems.value },
        { id: 'tabs', label: 'Open tabs', items: tabItems.value },
        routes,
        {
            id: 'environments',
            label: 'Environments',
            items: [...environmentItems.value, ...applicationItems.value],
        },
    ].filter(group => group.items.length > 0);
});
</script>

<template>
    <AppDialog
        v-model:open="isPaletteOpen"
        title="Command palette"
        description="Search routes, tabs, environments, and actions."
        :content="{ onCloseAutoFocus: restoreFocus }"
        :ui="{ content: 'max-w-dialog-lg top-20 translate-y-0' }"
    >
        <template #content>
            <AppCommand
                v-model:search-term="search"
                :groups="groups"
                :placeholder="
                    paletteMode === 'routes'
                        ? 'Open a route...'
                        : 'Search routes, tabs, and actions...'
                "
                :ui="{ content: 'max-h-80 overflow-y-auto' }"
                data-testid="command-palette"
            >
                <template #empty>Nothing matches your search.</template>
            </AppCommand>
        </template>
    </AppDialog>
</template>
