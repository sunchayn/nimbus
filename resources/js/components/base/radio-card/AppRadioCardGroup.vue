<script setup lang="ts">
/**
 * @component AppRadioCardGroup
 * @description Radio group of cards with a preview slot above a radio, a title, and a description.
 */
// Nuxt UI has no radio card with a preview area, so this wrapper uses reka-ui directly.
import { RadioGroupIndicator, RadioGroupItem, RadioGroupRoot } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppRadioCardItem {
    value: string;
    label: string;
    description?: string;
}

/*
 * Component Setup.
 */

defineProps<{
    items: AppRadioCardItem[];
    label: string;
}>();

const modelValue = defineModel<string>();

defineSlots<{
    preview(props: { item: AppRadioCardItem }): unknown;
}>();
</script>

<template>
    <RadioGroupRoot
        v-model="modelValue"
        :aria-label="label"
        orientation="horizontal"
        class="gap-base grid sm:grid-cols-3"
    >
        <RadioGroupItem
            v-for="item in items"
            :key="item.value"
            :value="item.value"
            :data-testid="`radio-card-${item.value}`"
            class="border-default focus-visible:ring-focus focus-visible:ring-accented data-[state=checked]:border-inverted flex cursor-pointer flex-col overflow-hidden rounded-md border text-left outline-none"
            @keydown.enter="modelValue = item.value"
        >
            <div aria-hidden="true" class="border-default w-full border-b">
                <slot name="preview" :item="item" />
            </div>
            <div class="gap-tight p-card flex flex-col">
                <div class="gap-base flex items-center">
                    <span
                        aria-hidden="true"
                        class="border-accented flex size-4 shrink-0 items-center justify-center rounded-full border"
                    >
                        <RadioGroupIndicator
                            class="bg-inverted text-inverted flex size-full items-center justify-center rounded-full"
                        >
                            <span class="i-hugeicons-tick-02 size-icon-xs" />
                        </RadioGroupIndicator>
                    </span>
                    <span class="text-sm font-medium">{{ item.label }}</span>
                </div>
                <span v-if="item.description" class="text-muted text-xs">
                    {{ item.description }}
                </span>
            </div>
        </RadioGroupItem>
    </RadioGroupRoot>
</template>
