<script setup lang="ts">
/**
 * @component AppInputPopover
 * @description A popover with one full width input and an optional header.
 * Enter submits the value. The default slot is the trigger and `#anchor` anchors it without a trigger.
 */
import { AppInput } from '@/components/base/input';
import { AppPopover } from '@/components/base/popover';

/*
 * Types & Interfaces.
 */

export interface AppInputPopoverProps {
    header?: string;
    placeholder?: string;
    /** Prefix of the test ids, which become `<prefix>-form` and `<prefix>-input`. */
    testId?: string;
}

/*
 * Component Setup.
 */

defineProps<AppInputPopoverProps>();

const open = defineModel<boolean>('open', { default: false });

const value = defineModel<string>({ required: true });

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <AppPopover
        v-model:open="open"
        :content="{ align: 'start' }"
        :ui="{ content: 'w-64 p-0' }"
    >
        <template v-if="$slots.default" #default>
            <slot />
        </template>

        <template v-if="$slots.anchor" #anchor>
            <slot name="anchor" />
        </template>

        <template #content>
            <form
                :data-testid="testId && `${testId}-form`"
                @submit.prevent="emit('submit')"
            >
                <p
                    v-if="header"
                    class="border-default text-muted px-base py-tight border-b text-xs"
                >
                    {{ header }}
                </p>
                <AppInput
                    v-model="value"
                    variant="none"
                    size="sm"
                    class="w-full"
                    :placeholder="placeholder"
                    :aria-label="header"
                    :data-testid="testId && `${testId}-input`"
                />
            </form>
        </template>
    </AppPopover>
</template>
