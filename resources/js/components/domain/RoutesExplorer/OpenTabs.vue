<script setup lang="ts">
/**
 * @component OpenTabs
 * @description Renders the list of open tabs in the sidebar with reordering support.
 */
import {
    AppCollapsible,
    AppCollapsibleContent,
    AppCollapsibleTrigger,
} from '@/components/base/collapsible';
import {
    AppSidebarGroup,
    AppSidebarGroupContent,
    AppSidebarMenuButton,
    AppSidebarMenuItem,
} from '@/components/base/sidebar';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import { AppScrollArea } from '@/components/base/scroll-area';
import { useTabVerticalScroll } from '@/composables/ui/useTabVerticalScroll';
import { useTabsStore } from '@/stores';
import { ChevronRight, XIcon } from 'lucide-vue-next';
import { type ComponentPublicInstance, computed, nextTick, ref, watch } from 'vue';
import draggable from 'vuedraggable';

/*
 * Stores.
 */

const tabsStore = useTabsStore();

/*
 * Vertical Scroll.
 */

const { scrollContainer, showTopMask, showBottomMask, updateScrollMasks, scrollTabIntoView } =
    useTabVerticalScroll({
        SCROLL_PADDING: 20,
        MASK_HEIGHT: 32,
    });

const tabElements = ref<Record<string, HTMLElement>>({});

const tabsModel = computed({
    get: () => tabsStore.tabs,
    set: val => {
        tabsStore.tabs = val;
    },
});

const setTabElement = (
    id: string,
    element: ComponentPublicInstance | HTMLElement | null,
) => {
    if (element) {
        tabElements.value[id] = (element as ComponentPublicInstance).$el || element;
    }
};

/*
 * Methods.
 */

const handleTabClick = (id: string) => {
    tabsStore.setActiveTab(id);

    const element = tabElements.value[id];
    if (element) {
        scrollTabIntoView(element);
    }
};
/*
 * Watch the active tab and scroll it into view when it changes.
 */
watch(
    () => tabsStore.activeTabId,
    async newId => {
        if (!newId) {
            return;
        }

        await nextTick();

        const element = tabElements.value[newId];
        if (element) {
            scrollTabIntoView(element);
        }
    },
    { immediate: true },
);

const handleCloseTab = (id: string) => {
    tabsStore.closeTab(id);
};

/*
 * State.
 */

const isOpen = defineModel<boolean>('isOpen');
const scrollAreaRef = ref<InstanceType<typeof AppScrollArea> | null>(null);

watch(
    () => scrollAreaRef.value?.viewport,
    viewport => {
        if (viewport) {
            scrollContainer.value = viewport;
        }
    },
);
</script>

<template>
    <AppSidebarGroup
        class="flex flex-col overflow-hidden p-0"
        :class="{ 'h-full': isOpen }"
        data-testid="open-tabs-section"
    >
        <AppSidebarGroupContent
            class="flex min-h-0 flex-col overflow-hidden"
            :class="{ 'flex-1': isOpen }"
        >
            <AppSidebarMenuItem
                class="flex max-h-full min-h-0 flex-col overflow-hidden p-0"
                :class="{ 'flex-1': isOpen }"
            >
                <AppCollapsible
                    class="group/collapsible flex min-h-0 flex-col overflow-hidden [&[data-state=open]>button>svg:first-child]:rotate-90"
                    :class="{ 'flex-1': isOpen }"
                    :open="isOpen"
                    @update:open="isOpen = $event"
                >
                    <AppCollapsibleTrigger as-child>
                        <AppSidebarMenuButton
                            class="focus-visible:bg-sidebar-accent data-[active=true]:focus-visible:bg-sidebar-accent text-xs focus-visible:ring-0"
                            data-testid="open-tabs-trigger"
                        >
                            <ChevronRight class="transition-transform" />
                            <span class="truncate">Open Tabs</span>
                        </AppSidebarMenuButton>
                    </AppCollapsibleTrigger>
                    <AppCollapsibleContent
                        class="relative flex min-h-0 flex-1 flex-col overflow-hidden border-t"
                    >
                        <!-- Top Mask -->
                        <div
                            class="from-sidebar pointer-events-none absolute top-0 right-0 left-0 z-10 h-8 bg-gradient-to-b from-20% to-transparent transition-opacity duration-300"
                            :class="[showTopMask && isOpen ? 'opacity-100' : 'opacity-0']"
                        />

                        <AppScrollArea
                            ref="scrollAreaRef"
                            class="min-h-0 flex-1"
                            @scroll="updateScrollMasks"
                        >
                            <draggable
                                v-model="tabsModel"
                                item-key="id"
                                tag="ul"
                                class="border-sidebar-border ml-2.5 flex min-w-0 translate-x-px flex-col gap-1 border-l py-0.5 pl-0.5 group-data-[collapsible=icon]:hidden"
                                ghost-class="tabs-ghost"
                            >
                                <template #item="{ element: tab }">
                                    <AppSidebarMenuItem
                                        :key="tab.id"
                                        class="group relative"
                                    >
                                        <AppSidebarMenuButton
                                            :ref="(el: any) => setTabElement(tab.id, el)"
                                            :is-active="tab.id === tabsStore.activeTabId"
                                            class="focus-visible:bg-sidebar-accent data-[active=true]:focus-visible:bg-sidebar-accent pl-2 text-sm focus-visible:ring-0 data-[active=true]:rounded-l-none"
                                            data-testid="sidebar-tab"
                                            @click="handleTabClick(tab.id)"
                                        >
                                            <HttpVerbLabel
                                                :method="tab.method"
                                                class="scale-90"
                                            />
                                            <span class="flex-1 truncate">
                                                {{ tab.title }}
                                            </span>
                                            <button
                                                class="hover:bg-accent/50 shrink-0 rounded p-0.5 opacity-0 transition-opacity group-hover:opacity-100"
                                                data-testid="sidebar-tab-close"
                                                @click.stop="handleCloseTab(tab.id)"
                                            >
                                                <XIcon class="size-3" />
                                            </button>
                                        </AppSidebarMenuButton>
                                    </AppSidebarMenuItem>
                                </template>
                            </draggable>
                        </AppScrollArea>

                        <!-- Bottom Scroll Mask -->
                        <div
                            class="from-sidebar pointer-events-none absolute right-0 bottom-0 left-0 z-10 h-8 bg-gradient-to-t from-20% to-transparent transition-opacity duration-300"
                            :class="[
                                showBottomMask && isOpen ? 'opacity-100' : 'opacity-0',
                            ]"
                        />
                    </AppCollapsibleContent>
                </AppCollapsible>
            </AppSidebarMenuItem>
        </AppSidebarGroupContent>
    </AppSidebarGroup>
</template>

<style scoped>
.tabs-ghost {
    opacity: 0.2;
    background-color: var(--sidebar-accent);
}
</style>
