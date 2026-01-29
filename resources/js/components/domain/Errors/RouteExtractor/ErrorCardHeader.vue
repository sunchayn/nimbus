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
        class="from-destructive/10 relative z-10 border-b bg-gradient-to-r to-transparent"
    >
        <div class="flex">
            <div class="bg-destructive/10 dark:bg-destructive/20 flex items-center px-4">
                <TriangleAlertIcon class="text-destructive dark:text-foreground size-4" />
            </div>
            <div class="p-2.5">
                <h2 class="text-md leading-tight font-semibold">
                    {{ props.message }}
                </h2>
                <p class="text-subtle-foreground text-sm">Route extraction failed</p>
            </div>
        </div>
    </div>
</template>
