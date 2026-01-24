<script setup lang="ts">
/**
 * @component AppCommandGroup
 * @description Groups related command items together with an optional heading.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { ListboxGroupProps } from 'reka-ui';
import { ListboxGroup, ListboxGroupLabel, useId } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed, onMounted, onUnmounted } from 'vue';
import { provideCommandGroupContext, useCommand } from '.';

/*
 * Types & Interfaces.
 */

export interface AppCommandGroupProps extends ListboxGroupProps {
    class?: HTMLAttributes['class'];
    heading?: string;
}

/*
 * Component Setup.
 */

const props = defineProps<AppCommandGroupProps>();

const delegatedProps = reactiveOmit(props, 'class');

/*
 * Computed & Methods.
 */

const { allGroups, filterState } = useCommand();
const id = useId();

const isRender = computed(() =>
    !filterState.search ? true : filterState.filtered.groups.has(id),
);

provideCommandGroupContext({ id });
onMounted(() => {
    if (!allGroups.value.has(id)) {
        allGroups.value.set(id, new Set());
    }
});
onUnmounted(() => {
    allGroups.value.delete(id);
});
</script>

<template>
    <ListboxGroup
        v-bind="delegatedProps"
        :id="id"
        data-slot="command-group"
        :class="cn('text-foreground overflow-hidden p-1', props.class)"
        :hidden="isRender ? undefined : true"
    >
        <ListboxGroupLabel
            v-if="heading"
            class="text-muted-foreground px-2 py-1.5 text-xs font-medium"
        >
            {{ heading }}
        </ListboxGroupLabel>
        <slot />
    </ListboxGroup>
</template>
