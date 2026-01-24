<script setup lang="ts">
/**
 * @component AppCommandDialog
 * @description A command palette wrapped in a modal dialog.
 */
import {
    AppDialog,
    AppDialogContent,
    AppDialogDescription,
    AppDialogHeader,
    AppDialogTitle,
} from '@/components/base/dialog';
import type { DialogRootEmits, DialogRootProps } from 'reka-ui';
import { useForwardPropsEmits } from 'reka-ui';
import AppCommand from './AppCommand.vue';

/*
 * Types & Interfaces.
 */

export interface AppCommandDialogProps extends DialogRootProps {
    title?: string;
    description?: string;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppCommandDialogProps>(), {
    title: 'Command Palette',
    description: 'Search for a command to run...',
});
const emits = defineEmits<DialogRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <AppDialog v-bind="forwarded">
        <AppDialogContent class="overflow-hidden p-0">
            <AppDialogHeader class="sr-only">
                <AppDialogTitle>{{ title }}</AppDialogTitle>
                <AppDialogDescription>{{ description }}</AppDialogDescription>
            </AppDialogHeader>
            <AppCommand>
                <slot />
            </AppCommand>
        </AppDialogContent>
    </AppDialog>
</template>
