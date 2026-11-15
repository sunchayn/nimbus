<script setup lang="ts">
/**
 * @component AppInputPopover
 * @description A popover with one full width input, laid out like the search row of a dropdown.
 * A header, when passed, shows as a label row above the input and also names it for assistive technology.
 * An optional leading icon marks the purpose of the input.
 * Enter submits the value. The default slot is the trigger and `#anchor` anchors it without a trigger.
 */
import { AppInput } from '@/components/base/input';
import { AppPopover } from '@/components/base/popover';

/*
 * Types & Interfaces.
 */

export interface AppInputPopoverProps {
    header?: string;
    leadingIcon?: string;
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
                    class="px-control-sm py-tight text-menu-label border-b-line border-default text-muted font-semibold"
                >
                    {{ header }}
                </p>
                <AppInput
                    v-model="value"
                    variant="none"
                    size="sm"
                    class="w-full"
                    :leading-icon="leadingIcon"
                    :placeholder="placeholder"
                    :aria-label="header"
                    :data-testid="testId && `${testId}-input`"
                />
            </form>
        </template>
    </AppPopover>
</template>
