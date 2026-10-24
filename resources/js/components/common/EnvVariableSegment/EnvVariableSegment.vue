<script setup lang="ts">
/**
 * @component EnvVariableSegment
 * @description Popover that shows the status and value of an environment variable inside an input.
 */
import { AppPopover } from '@/components/base/popover';
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
    <AppPopover
        :open="isOpen && status !== EnvVariableCheckStatus.None"
        :content="{ side: 'bottom', align: 'start', alignOffset }"
    >
        <template #anchor>
            <slot />
        </template>

        <template #content>
            <div
                :class="{
                    'p-panel': status !== EnvVariableCheckStatus.Resolved,
                    'from-info/5 dark:from-info/30 bg-gradient-to-tr to-transparent to-50% p-1':
                        status === EnvVariableCheckStatus.Resolved,
                    'from-warning/5 dark:from-warning/30 bg-gradient-to-tr to-transparent to-50% py-1':
                        status === EnvVariableCheckStatus.Empty,
                    'from-error/5 dark:from-error/30 bg-gradient-to-tr to-transparent to-50% py-1':
                        status === EnvVariableCheckStatus.Missing,
                }"
            >
                <p
                    v-if="status === EnvVariableCheckStatus.Missing"
                    class="text-muted leading-tight"
                >
                    The referenced variable cannot be found in the selected collection.
                </p>
                <p
                    v-else-if="status === EnvVariableCheckStatus.Empty"
                    class="text-muted leading-tight"
                >
                    The referenced variable is found, but its value is empty.
                </p>
                <span v-else>&lt;{{ variableValue }}&gt;</span>
            </div>
        </template>
    </AppPopover>
</template>
