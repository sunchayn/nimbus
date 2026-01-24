<script setup lang="ts">
/**
 * @component ErrorCardHeader
 * @description Header for the error card displaying the error message.
 */
import { TriangleAlertIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppErrorCardHeaderProps {
    message: string;
}

/*
 * Component Setup.
 */

const props = defineProps<AppErrorCardHeaderProps>();
</script>

<template>
    <div
        class="relative z-10 border-b bg-gradient-to-r from-destructive/10 to-transparent"
    >
        <div class="flex gap-3">
            <div class="flex items-center bg-destructive/10 px-6 py-3 dark:bg-destructive/20">
                <TriangleAlertIcon class="size-6 text-destructive dark:text-foreground" />
            </div>
            <div class="p-3">
                <h2 class="text-md leading-tight font-semibold">
                    {{ props.message }}
                </h2>
                <p class="text-subtle-foreground text-sm">Route extraction failed</p>
            </div>
        </div>
    </div>
</template>
