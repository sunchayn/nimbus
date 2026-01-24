<script setup lang="ts">
/**
 * @component AppSheetDescription
 * @description Supporting text for the sheet content.
 */
import { cn } from '@/utils/ui';
import { DialogDescription, type DialogDescriptionProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSheetDescriptionProps extends DialogDescriptionProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetDescriptionProps>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <DialogDescription
        :class="cn('text-subtle text-sm', props.class)"
        v-bind="delegatedProps"
    >
        <slot />
    </DialogDescription>
</template>
