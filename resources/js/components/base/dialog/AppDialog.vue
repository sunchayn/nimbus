<script setup lang="ts">
/**
 * @component AppDialog
 * @description Root container for a modal dialog.
 */
import type { DialogRootEmits, DialogRootProps } from 'reka-ui';
import { DialogRoot, useForwardPropsEmits } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDialogProps extends DialogRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogProps>();
const emits = defineEmits<DialogRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DialogRoot data-slot="dialog" v-bind="forwarded">
        <slot />
    </DialogRoot>
</template>
