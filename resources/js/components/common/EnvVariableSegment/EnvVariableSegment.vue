<script setup lang="ts">
import {
    AppPopover,
    AppPopoverAnchor,
    AppPopoverContent,
} from '@/components/base/popover';
import { EnvVariableCheckStatus } from '@/interfaces/common/env-vars';

/*
 * Types & Interfaces.
 */

export interface Props {
    status: EnvVariableCheckStatus;
    alignOffset?: number;
    variableValue?: string;
    isOpen?: boolean;
}

/*
 * Component Setup.
 */

withDefaults(defineProps<Props>(), {
    variableValue: undefined,
    alignOffset: 0,
    isOpen: false,
});
</script>

<template>
    <AppPopover :open="isOpen">
        <AppPopoverAnchor as-child>
            <slot />
        </AppPopoverAnchor>

        <AppPopoverContent
            v-if="status !== EnvVariableCheckStatus.None"
            side="bottom"
            align="start"
            class="w-fit p-0 text-xs font-medium"
            :align-offset="alignOffset"
            :class="{
                'max-w-64': status !== EnvVariableCheckStatus.Resolved,
            }"
        >
            <div
                :class="{
                    'p-panel': status !== EnvVariableCheckStatus.Resolved,
                    'bg-gradient-to-tr from-blue-500/5 to-transparent to-50% p-1 py-0.5 dark:from-blue-700/30':
                        status === EnvVariableCheckStatus.Resolved,
                    'bg-gradient-to-tr from-yellow-500/5 to-transparent to-50% py-0.5 dark:from-yellow-700/30':
                        status === EnvVariableCheckStatus.Empty,
                    'bg-gradient-to-tr from-rose-500/5 to-transparent to-50% py-0.5 dark:from-rose-700/30':
                        status === EnvVariableCheckStatus.Missing,
                }"
            >
                <p
                    v-if="status === EnvVariableCheckStatus.Missing"
                    class="text-muted-foreground leading-tight"
                >
                    The referenced variable cannot be found in the selected collection.
                </p>
                <p
                    v-else-if="status === EnvVariableCheckStatus.Empty"
                    class="text-muted-foreground leading-tight"
                >
                    The referenced variable is found, but its value is empty.
                </p>
                <span v-else>&lt;{{ variableValue }}&gt;</span>
            </div>
        </AppPopoverContent>
    </AppPopover>
</template>
