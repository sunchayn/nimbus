<script setup lang="ts">
/**
 * @component AppTabsContent
 * @description The content area for an individual tab panel.
 */
import { cn } from '@/utils/ui';
import { TabsContent, type TabsContentProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTabsContentProps extends TabsContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTabsContentProps>();

/*
 * Computed & Methods.
 */

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <TabsContent
        :class="cn('mt-2 ring-offset-white focus-visible:outline-none', props.class)"
        v-bind="delegatedProps"
    >
        <slot />
    </TabsContent>
</template>
