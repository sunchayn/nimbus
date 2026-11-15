<script setup lang="ts">
/**
 * @component DocumentationLinkButton
 * @description A consistent external link button pointing to the application documentation.
 */
import { AppButton } from '@/components/base/button';
import { USER_GUIDE_LINK } from '@/config';
import type { ButtonProps } from '@nuxt/ui/components/Button.vue';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface DocumentationLinkButtonProps {
    class?: HTMLAttributes['class'];
    /** A section of the user guide, such as `workspace`. */
    section?: string;
    variant?: ButtonProps['variant'];
    label?: string;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<DocumentationLinkButtonProps>(), {
    class: undefined,
    section: undefined,
    variant: undefined,
    label: 'Documentation',
});

const href = computed(() =>
    props.section ? `${USER_GUIDE_LINK}#${props.section}` : USER_GUIDE_LINK,
);
</script>

<template>
    <AppButton
        size="sm"
        :variant="variant"
        as="a"
        :href="href"
        target="_blank"
        icon="i-hugeicons-link-square-02"
        :label="label"
    />
</template>
