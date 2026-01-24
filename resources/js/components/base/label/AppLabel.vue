<script setup lang="ts">
/**
 * @component AppLabel
 * @description Primitive label component with consistency across themes.
 */
import { cn } from '@/utils/ui';
import { Label, type LabelProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppLabelProps extends LabelProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppLabelProps>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <Label
        v-bind="delegatedProps"
        :class="
            cn(
                'text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
                props.class,
            )
        "
    >
        <slot />
    </Label>
</template>
