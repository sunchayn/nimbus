<script setup lang="ts">
/**
 * @component AppCommandEmpty
 * @description Renders content when no command items match the filter.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { PrimitiveProps } from 'reka-ui';
import { Primitive } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { useCommand } from '.';

/*
 * Types & Interfaces.
 */

export interface AppCommandEmptyProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppCommandEmptyProps>();

const delegatedProps = reactiveOmit(props, 'class');

/*
 * Computed & Methods.
 */

const { filterState } = useCommand();
const isRender = computed(() => !!filterState.search && filterState.filtered.count === 0);
</script>

<template>
    <Primitive
        v-if="isRender"
        data-slot="command-empty"
        v-bind="delegatedProps"
        :class="cn('py-6 text-center text-sm', props.class)"
    >
        <slot />
    </Primitive>
</template>
