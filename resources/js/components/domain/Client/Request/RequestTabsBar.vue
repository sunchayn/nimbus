<script setup lang="ts">
/**
 * @component RequestTabsBar
 * @description A horizontal strip of the open request tabs with reordering support.
 */
import { AppContextMenu, type AppContextMenuItem } from '@/components/base/context-menu';
import AppIcon from '@/components/base/icons/AppIcon.vue';
import { AppInputPopover } from '@/components/base/popover';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import ExplorerToggleButton from '@/components/domain/RoutesExplorer/ExplorerToggleButton.vue';
import { useShareableLink } from '@/composables/request/useShareableLink';
import type { Tab } from '@/interfaces/ui/tabs';
import { useTabsStore } from '@/stores/request/useTabsStore';
import { useScroll, useTimeoutFn } from '@vueuse/core';
import { computed, nextTick, ref, shallowRef, useTemplateRef, watch } from 'vue';
import draggable from 'vuedraggable';
import ShareableLinkDialog from './RequestBuilder/ShareableLinkDialog.vue';
import { shortTitle } from '@/utils/request/tabTitle';

/*
 * Stores.
 */

const tabsStore = useTabsStore();
const { showShareableLinkDialog, shareableLink, openShareableLinkDialog } =
    useShareableLink();

/*
 * State.
 */

const tabElements = ref<Record<string, HTMLElement>>({});
const tabsList = useTemplateRef<{ $el: HTMLElement }>('tabsList');
const listElement = computed(() => tabsList.value?.$el ?? null);
const { arrivedState } = useScroll(listElement);

const tabsModel = computed({
    get: () => tabsStore.tabs,
    set: value => {
        tabsStore.tabs = value;
    },
});

/*
 * Methods.
 */

const tabTitle = (tab: Tab) =>
    tab.customTitle ?? tab.workspace?.name ?? shortTitle(tab.title);

/*
 * Rename.
 */

const renamingTabId = shallowRef<string | null>(null);
const renameDraft = shallowRef('');

const startRename = (tab: Tab) => {
    tabsStore.setActiveTab(tab.id);
    renameDraft.value = tabTitle(tab);
    renamingTabId.value = tab.id;
};

const submitRename = () => {
    if (renamingTabId.value) {
        tabsStore.renameTab(renamingTabId.value, renameDraft.value);
    }

    renamingTabId.value = null;
};

/*
 * Context menu.
 */

const shareTab = (id: string) => {
    tabsStore.setActiveTab(id);
    openShareableLinkDialog();
};

const menuItems = (tab: Tab, index: number): AppContextMenuItem[][] => {
    const closeActions: AppContextMenuItem[] = [
        {
            label: 'Close tab',
            icon: 'i-hugeicons-cancel-01',
            onSelect: () => closeTab(tab.id),
        },
    ];

    if (tabsStore.tabs.some(other => other.id !== tab.id && !other.pinned)) {
        closeActions.push({
            label: 'Close other tabs',
            icon: 'i-hugeicons-column-delete',
            onSelect: () => tabsStore.closeOtherTabs(tab.id),
        });
    }

    if (tabsStore.tabs.some((other, i) => i < index && !other.pinned)) {
        closeActions.push({
            label: 'Close tabs to the left',
            icon: 'i-hugeicons-flip-left',
            onSelect: () => tabsStore.closeTabsToLeft(tab.id),
        });
    }

    if (tabsStore.tabs.some((other, i) => i > index && !other.pinned)) {
        closeActions.push({
            label: 'Close tabs to the right',
            icon: 'i-hugeicons-flip-right',
            onSelect: () => tabsStore.closeTabsToRight(tab.id),
        });
    }

    return [
        closeActions,
        [
            {
                label: 'Rename tab',
                icon: 'i-hugeicons-label',
                afterClose: () => startRename(tab),
            },
            {
                label: tab.pinned ? 'Unpin tab' : 'Pin tab',
                icon: tab.pinned ? 'i-hugeicons-pin-off' : 'i-hugeicons-pin',
                onSelect: () => tabsStore.togglePinTab(tab.id),
            },
            {
                label: 'Share',
                icon: 'i-hugeicons-link',
                onSelect: () => shareTab(tab.id),
            },
        ],
    ];
};

/*
 * Tab motion.
 */

// Tabs restored on load appear instantly, and only tabs added later grow in.
const restoredTabIds = new Set(tabsStore.tabs.map(tab => tab.id));

const closingTabId = shallowRef<string | null>(null);

const { start: startClosing } = useTimeoutFn(
    (id: string) => {
        tabsStore.closeTab(id);
        closingTabId.value = null;
    },
    150,
    { immediate: false },
);

// The tab collapses first, and it leaves the store once the animation is done.
const closeTab = (id: string) => {
    if (closingTabId.value === id) {
        return;
    }

    closingTabId.value = id;
    startClosing(id);
};

const setTabElement = (id: string, element: unknown) => {
    if (element instanceof HTMLElement) {
        tabElements.value[id] = element;
    }
};

const scrollIntoView = async (id: string | null | undefined) => {
    if (!id) {
        return;
    }

    await nextTick();

    tabElements.value[id]?.scrollIntoView?.({
        inline: 'nearest',
        block: 'nearest',
    });
};

watch(() => tabsStore.activeTabId, scrollIntoView, { immediate: true });
</script>

<template>
    <div class="gap-pane flex shrink-0 items-center" data-testid="request-tabs-bar">
        <ExplorerToggleButton class="shrink-0" />
        <span
            v-if="!tabsStore.tabs.length"
            class="text-muted px-base text-sm"
            data-testid="request-tabs-empty"
        >
            No open requests
        </span>
        <draggable
            v-else
            ref="tabsList"
            v-model="tabsModel"
            item-key="id"
            tag="ul"
            class="no-scrollbar gap-pane flex min-w-0 flex-1 items-center overflow-x-auto"
            :class="{
                'mask-fade-start': !arrivedState.left,
                'mask-fade-end': !arrivedState.right,
            }"
            ghost-class="opacity-30"
        >
            <template #item="{ element: tab, index }">
                <li
                    :key="tab.id"
                    class="group shrink-0"
                    :class="{
                        'tab-in': !restoredTabIds.has(tab.id),
                        'tab-closing': closingTabId === tab.id,
                    }"
                >
                    <AppContextMenu :items="menuItems(tab, index)">
                        <div
                            :ref="(el: unknown) => setTabElement(tab.id, el)"
                            :data-active="tab.id === tabsStore.activeTabId"
                            class="bg-elevated border-default hover:bg-accented data-[active=true]:bg-default data-[active=true]:border-tab-active-border data-[active=true]:text-highlighted data-[active=true]:shadow-tab-active text-toned h-control-md gap-tight ps-base max-w-tab pe-hair relative flex cursor-pointer items-center rounded-sm border text-sm"
                            data-testid="sidebar-tab"
                            @click="tabsStore.setActiveTab(tab.id)"
                            @dblclick="startRename(tab)"
                        >
                            <AppIcon
                                v-if="tab.pinned"
                                name="i-hugeicons-pin"
                                class="size-icon-xs shrink-0"
                                data-testid="sidebar-tab-pinned"
                            />
                            <button
                                type="button"
                                :aria-current="
                                    tab.id === tabsStore.activeTabId ? 'page' : undefined
                                "
                                :title="tab.customTitle ?? tab.title"
                                tabindex="-1"
                                class="gap-tight flex min-w-0 items-baseline outline-none"
                            >
                                <HttpVerbLabel
                                    :method="tab.method"
                                    class="min-w-0! shrink-0"
                                />
                                <span class="truncate">{{ tabTitle(tab) }}</span>
                            </button>
                            <button
                                type="button"
                                aria-label="Close tab"
                                class="hover:bg-inverted/10 h-control-xs w-control-xs flex shrink-0 items-center justify-center rounded-sm opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100 pointer-coarse:opacity-100"
                                data-testid="sidebar-tab-close"
                                @click.stop="closeTab(tab.id)"
                            >
                                <AppIcon
                                    name="i-hugeicons-cancel-01"
                                    class="size-icon-xs"
                                />
                            </button>
                            <AppInputPopover
                                v-model="renameDraft"
                                :open="renamingTabId === tab.id"
                                header="Label"
                                leading-icon="i-hugeicons-label"
                                placeholder="Tab label"
                                test-id="tab-rename"
                                @update:open="open => !open && (renamingTabId = null)"
                                @submit="submitRename"
                            >
                                <template #anchor>
                                    <span class="pointer-events-none absolute inset-0" />
                                </template>
                            </AppInputPopover>
                        </div>
                    </AppContextMenu>
                </li>
            </template>
        </draggable>
    </div>

    <ShareableLinkDialog v-model:open="showShareableLinkDialog" :link="shareableLink" />
</template>
