<script setup lang="ts">
/**
 * @component DocumentationLinkButton
 * @description A consistent external link button pointing to the application documentation.
 */
import { AppButton } from '@/components/base/button';
import { USER_GUIDE_LINK } from '@/config';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface DocumentationLinkButtonProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

defineProps<DocumentationLinkButtonProps>();
</script>

<template>
    <AppButton
        size="xs"
        as="a"
        :href="USER_GUIDE_LINK"
        target="_blank"
        icon="i-hugeicons-link-square-02"
        label="Documentation"
    />
</template>
