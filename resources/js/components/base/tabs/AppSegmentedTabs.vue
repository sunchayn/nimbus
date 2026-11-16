<script setup lang="ts" generic="T extends TabsItem">
/**
 * @component AppSegmentedTabs
 * @description A pill toggle on a bordered track, such as the Routes and Workspace switch.
 * It fills its row by default, and `fill` off makes it as wide as its tabs.
 * `outlined` gives the inactive tabs a faint border.
 * Anything else passes through to AppTabs, and `ui` adds classes to the base ones.
 */
import AppTabs from '@/components/base/tabs/AppTabs.vue';
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { TabsItem, TabsProps, TabsSlots } from '@nuxt/ui/components/Tabs.vue';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSegmentedTabsProps<Item extends TabsItem> extends Omit<
    TabsProps<Item>,
    'modelValue' | 'variant' | 'color'
> {
    fill?: boolean;
    outlined?: boolean;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppSegmentedTabsProps<T>>(), {
    fill: true,
    outlined: false,
    size: 'sm',
});

const modelValue = defineModel<string | number>();

defineSlots<TabsSlots<T>>();

const passed = useForwardedProps(props);

// The own props are applied below, so they are not forwarded as they are.
const forwarded = computed(() =>
    Object.fromEntries(
        Object.entries(passed.value).filter(
            ([key]) => key !== 'fill' && key !== 'outlined' && key !== 'ui',
        ),
    ),
);

/*
 * Computed & Methods.
 */

const join = (...classes: unknown[]) => classes.filter(Boolean).join(' ');

const ui = computed(() => ({
    ...props.ui,
    list: join(
        'bg-elevated border-default border',
        props.orientation !== 'vertical' && 'h-control-md',
        props.orientation !== 'vertical' && props.fill && 'w-full',
        props.orientation !== 'vertical' && !props.fill && 'w-fit max-w-full',
        props.ui?.list,
    ),
    trigger: join(
        'text-muted data-[state=active]:text-highlighted',
        props.orientation !== 'vertical' && 'h-full py-0',
        props.orientation === 'vertical' && 'flex-none',
        props.outlined &&
            'border-default/60 data-[state=active]:border-transparent border',
        props.orientation !== 'vertical' && props.fill && 'flex-1',
        props.ui?.trigger,
    ),
    indicator: join('bg-default border-inverted border shadow-none', props.ui?.indicator),
}));
</script>

<template>
    <AppTabs
        v-bind="forwarded"
        v-model="modelValue"
        :size="size"
        variant="pill"
        color="neutral"
        :ui="ui"
        :class="{ 'w-full': fill && orientation !== 'vertical' }"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </AppTabs>
</template>
