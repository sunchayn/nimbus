<script setup lang="ts">
/**
 * @component AppSheet
 * @description Root container for a sheet component.
 */
import {
    DialogRoot,
    type DialogRootEmits,
    type DialogRootProps,
    useForwardPropsEmits,
} from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppSheetProps extends DialogRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetProps>();
const emits = defineEmits<DialogRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DialogRoot v-bind="forwarded">
        <slot />
    </DialogRoot>
</template>
