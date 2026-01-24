<script setup lang="ts">
/**
 * @component DocumentationLinkButton
 * @description A consistent external link button pointing to the application documentation.
 */
import { AppButton } from '@/components/base/button';
import { ExternalLinkIcon } from 'lucide-vue-next';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDocumentationLinkButtonProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

defineProps<AppDocumentationLinkButtonProps>();
</script>

<template>
    <AppButton
        size="xs"
        as="a"
        href="https://github.com/sunchayn/nimbus/tree/base/wiki/user-guide"
        target="_blank"
    >
        <ExternalLinkIcon />
        Documentation
    </AppButton>
</template>
