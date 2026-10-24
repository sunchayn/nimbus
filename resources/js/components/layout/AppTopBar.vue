<script setup lang="ts">
/**
 * @component AppTopBar
 * @description The application bar with the brand, the active environment, the current request and the app level actions.
 */
import { AppButton } from '@/components/base/button';
import { AppDropdownMenu } from '@/components/base/dropdown-menu';
import AppBrandIcon from '@/components/base/icons/AppBrandIcon.vue';
import { AppTooltip } from '@/components/base/tooltip';
import { useExplorerVisibility } from '@/composables/ui/useExplorerVisibility';
import { useEnvironmentVariablesStore } from '@/stores';
import { useEventListener } from '@vueuse/core';
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

/*
 * Component Setup.
 */

const router = useRouter();
const route = useRoute();
const environmentStore = useEnvironmentVariablesStore();
const { toggleExplorer } = useExplorerVisibility();

/*
 * Constants.
 */

const openExternal = (href: string) => {
    window.open(href, '_blank', 'noopener,noreferrer');
};

const secondaryPages = ['environments', 'settings'];

/*
 * Computed & Methods.
 */

const isMain = computed(() => !secondaryPages.includes(String(route.name)));

// Cmd or Ctrl plus B toggles the routes sidebar on the main page.
useEventListener(window, 'keydown', event => {
    if (
        isMain.value &&
        (event.metaKey || event.ctrlKey) &&
        !event.altKey &&
        !event.shiftKey &&
        event.key.toLowerCase() === 'b'
    ) {
        event.preventDefault();
        toggleExplorer();
    }
});

const environmentItems = computed(() => [
    ...(environmentStore.collections.length
        ? [
              environmentStore.collections.map(collection => ({
                  label: collection.name,
                  type: 'checkbox' as const,
                  checked: collection.id === environmentStore.activeCollectionId,
                  onSelect: () => environmentStore.select(collection.id),
              })),
          ]
        : []),
    [
        {
            label: 'Manage environments',
            icon: 'i-hugeicons-all-bookmark',
            onSelect: () => router.push({ name: 'environments' }),
        },
    ],
]);

const links = [
    {
        label: 'Documentation',
        icon: 'i-hugeicons-book-open-01',
        onClick: () =>
            openExternal(
                'https://github.com/sunchayn/nimbus/tree/base/wiki/user-guide#getting-started',
            ),
    },
    {
        label: 'GitHub',
        icon: 'i-hugeicons-github',
        onClick: () => openExternal('https://github.com/sunchayn/nimbus'),
    },
];

const pageAction = (label: string, icon: string, name: string, active: boolean) => ({
    label,
    icon,
    active,
    onClick: () => router.push({ name }),
});

const pages = computed(() => [
    pageAction('HTTP Client', 'i-hugeicons-internet-antenna-03', 'main', isMain.value),
]);

const settings = computed(() =>
    pageAction(
        'Settings',
        'i-hugeicons-settings-01',
        'settings',
        route.name === 'settings',
    ),
);
</script>

<template>
    <header
        class="h-toolbar px-base bg-default grid grid-cols-[1fr_auto_1fr] items-center gap-2 border-b"
        data-testid="app-top-bar"
    >
        <div class="flex min-w-0 items-center gap-2">
            <div class="flex items-center gap-2">
                <AppBrandIcon class="size-icon-lg" />
                <span class="text-highlighted text-sm font-semibold max-sm:hidden">
                    Nimbus
                </span>
            </div>

            <div class="border-default h-4 border-l" aria-hidden="true" />

            <div class="flex items-center gap-1">
                <AppTooltip
                    v-for="action in pages"
                    :key="action.label"
                    :text="action.label"
                >
                    <AppButton
                        :aria-label="action.label"
                        :icon="action.icon"
                        color="neutral"
                        :variant="action.active ? 'soft' : 'ghost'"
                        size="sm"
                        @click="action.onClick"
                    />
                </AppTooltip>
            </div>
        </div>

        <AppDropdownMenu :items="environmentItems" :content="{ align: 'center' }">
            <AppButton
                color="neutral"
                variant="ghost"
                size="sm"
                trailing-icon="i-hugeicons-arrow-down-01"
                :label="environmentStore.activeCollection?.name ?? 'No environment'"
                data-testid="environment-switcher"
            />
        </AppDropdownMenu>

        <div class="flex items-center justify-end gap-2">
            <div class="max-xs:hidden flex items-center gap-1">
                <AppTooltip
                    v-for="action in links"
                    :key="action.label"
                    :text="action.label"
                >
                    <AppButton
                        :aria-label="action.label"
                        :icon="action.icon"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="action.onClick"
                    />
                </AppTooltip>
            </div>

            <div class="border-default max-xs:hidden h-4 border-l" aria-hidden="true" />

            <AppTooltip :text="settings.label">
                <AppButton
                    :aria-label="settings.label"
                    :icon="settings.icon"
                    color="neutral"
                    :variant="settings.active ? 'soft' : 'ghost'"
                    size="sm"
                    @click="settings.onClick"
                />
            </AppTooltip>
        </div>
    </header>
</template>
