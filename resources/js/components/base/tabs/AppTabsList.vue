<script setup lang="ts">
/**
 * @component AppTabsList
 * @description The container for tab triggers, typically styled as a bar or row.
 */
import { cn } from '@/utils/ui';
import { TabsList, type TabsListProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTabsListProps extends TabsListProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTabsListProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <TabsList
        v-bind="delegatedProps"
        :class="
            cn(
                'text-subtle inline-flex items-center justify-center rounded-sm p-1',
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
