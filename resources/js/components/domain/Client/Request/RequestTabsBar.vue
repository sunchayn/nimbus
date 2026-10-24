<script setup lang="ts">
/**
 * @component RequestTabsBar
 * @description A horizontal strip of the open request tabs with reordering support.
 */
import AppIcon from '@/components/base/icons/AppIcon.vue';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import ExplorerToggleButton from '@/components/domain/RoutesExplorer/ExplorerToggleButton.vue';
import { useTabsStore } from '@/stores';
import { useScroll, useTimeoutFn } from '@vueuse/core';
import {
    computed,
    nextTick,
    onMounted,
    ref,
    shallowRef,
    useTemplateRef,
    watch,
} from 'vue';
import draggable from 'vuedraggable';

/*
 * Stores.
 */

const tabsStore = useTabsStore();

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

// Shows the route without its resource prefix.
const shortTitle = (title: string) => {
    const segments = title.split('/').filter(Boolean);

    return segments.length > 1 ? segments.slice(1).join('/') : title;
};

/*
 * Tab motion.
 */

// Tabs restored on load appear instantly, and only tabs added later grow in.
const motionReady = shallowRef(false);
onMounted(() => nextTick(() => (motionReady.value = true)));

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
        <div
            v-if="!tabsStore.tabs.length"
            class="bg-default text-toned border-tab-active-border shadow-tab-active h-control-md gap-base px-base flex items-center rounded-md border text-sm"
            data-testid="request-tabs-empty"
        >
            <AppIcon name="i-hugeicons-add-01" class="size-icon-xs" />
            <span>New request</span>
        </div>
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
            <template #item="{ element: tab }">
                <li
                    :key="tab.id"
                    class="group shrink-0"
                    :class="{
                        'tab-in': motionReady,
                        'tab-closing': closingTabId === tab.id,
                    }"
                >
                    <div
                        :ref="(el: unknown) => setTabElement(tab.id, el)"
                        :data-active="tab.id === tabsStore.activeTabId"
                        class="bg-elevated border-default hover:bg-accented data-[active=true]:bg-default data-[active=true]:border-tab-active-border data-[active=true]:text-highlighted data-[active=true]:shadow-tab-active text-toned h-control-md gap-tight ps-base flex max-w-56 cursor-pointer items-center rounded-md border pe-1 text-sm"
                        data-testid="sidebar-tab"
                        @click="tabsStore.setActiveTab(tab.id)"
                    >
                        <button
                            type="button"
                            :aria-current="
                                tab.id === tabsStore.activeTabId ? 'page' : undefined
                            "
                            :title="tab.title"
                            class="focus-visible:ring-primary gap-tight flex min-w-0 items-center rounded-sm focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <HttpVerbLabel :method="tab.method" class="min-w-0!" />
                            <span class="truncate">{{ shortTitle(tab.title) }}</span>
                        </button>
                        <button
                            type="button"
                            aria-label="Close tab"
                            class="hover:bg-accented focus-visible:ring-primary shrink-0 rounded-sm p-1 opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100 focus-visible:ring-2 pointer-coarse:opacity-100"
                            data-testid="sidebar-tab-close"
                            @click.stop="closeTab(tab.id)"
                        >
                            <AppIcon name="i-hugeicons-cancel-01" class="size-icon-xs" />
                        </button>
                    </div>
                </li>
            </template>
        </draggable>
    </div>
</template>
