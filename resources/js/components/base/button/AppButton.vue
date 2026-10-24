<script setup lang="ts">
/**
 * @component AppButton
 * @description Button built on Nuxt UI with its color, variant and size vocabulary.
 * The default slot is forwarded only when given, so icon-only buttons stay square.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { ButtonProps } from '@nuxt/ui/components/Button.vue';
import UButton from '@nuxt/ui/components/Button.vue';

/*
 * Types & Interfaces.
 */

export interface AppButtonProps extends ButtonProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppButtonProps>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UButton v-bind="forwarded">
        <template v-if="$slots.default" #default>
            <slot />
        </template>
        <template v-if="$slots.leading" #leading>
            <slot name="leading" />
        </template>
    </UButton>
</template>
