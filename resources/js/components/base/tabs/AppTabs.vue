<script setup lang="ts" generic="T extends TabsItem">
/**
 * @component AppTabs
 * @description Tabs built on Nuxt UI. Inactive panels stay mounted so their state survives tab changes.
 * A list that overflows fades at the scrollable edges and scrolls the active tab into view.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import type { TabsItem, TabsProps, TabsSlots } from '@nuxt/ui/components/Tabs.vue';
import UTabs from '@nuxt/ui/components/Tabs.vue';
import { nextTick, onMounted, type ComponentPublicInstance } from 'vue';

/*
 * Component Setup.
 */

const props = defineProps<Omit<TabsProps<T>, 'modelValue'>>();

const modelValue = defineModel<string | number>();

defineSlots<TabsSlots<T>>();

const forwarded = useForwardedProps(props);

const { scrollContainer, showLeftMask, showRightMask, scrollTabIntoView } =
    useTabHorizontalScroll();

/*
 * Computed & Methods.
 */

// A function ref runs before mounted hooks, so the composable can attach its scroll listener.
const bindList = (instance: Element | ComponentPublicInstance | null) => {
    const root = (instance as ComponentPublicInstance | null)?.$el as
        | HTMLElement
        | undefined;

    scrollContainer.value =
        root?.querySelector<HTMLElement>(':scope > [data-slot="list"]') ?? null;
};

// Panels are not tab stops, so Tab moves from the active tab straight into its content.
const removePanelsFromTabOrder = () => {
    scrollContainer.value?.parentElement
        ?.querySelectorAll('[role="tabpanel"]')
        .forEach(panel => panel.setAttribute('tabindex', '-1'));
};

onMounted(() => nextTick(removePanelsFromTabOrder));

const handleUpdate = async (value: string | number) => {
    modelValue.value = value;

    await nextTick();

    removePanelsFromTabOrder();

    const active = scrollContainer.value?.querySelector<HTMLElement>(
        '[role="tab"][data-state="active"]',
    );

    if (active) {
        scrollTabIntoView(active);
    }
};
</script>

<template>
    <UTabs
        :ref="bindList"
        :unmount-on-hide="false"
        v-bind="forwarded"
        :model-value="modelValue"
        data-tabs-scroll
        :data-mask-start="showLeftMask || undefined"
        :data-mask-end="showRightMask || undefined"
        @update:model-value="handleUpdate"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UTabs>
</template>
