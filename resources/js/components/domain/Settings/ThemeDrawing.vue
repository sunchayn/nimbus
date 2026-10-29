<script setup lang="ts">
/**
 * @component ThemeDrawing
 * @description Small drawing of the workspace. The `light` and `dark` classes scope the theme tokens to it.
 */
defineProps<{ mode: 'light' | 'dark' }>();
</script>

<template>
    <div
        :class="mode"
        class="bg-default text-default gap-tight flex size-full flex-col overflow-hidden"
    >
        <div
            class="bg-muted border-default gap-tight px-base flex h-3 items-center border-b"
        >
            <span v-for="n in 3" :key="n" class="bg-accented h-1 w-6 rounded-full" />
        </div>
        <div class="gap-tight px-tight pb-tight flex flex-1">
            <div class="bg-muted gap-tight p-tight flex w-1/4 flex-col rounded-sm">
                <span v-for="n in 4" :key="n" class="bg-accented h-1 rounded-full" />
            </div>
            <div class="gap-tight flex flex-1 flex-col">
                <div class="gap-tight flex items-center">
                    <span class="bg-inverted h-2 w-5 rounded-sm" />
                    <span
                        class="bg-elevated border-default h-2 flex-1 rounded-sm border"
                    />
                </div>
                <div class="bg-muted border-default flex-1 rounded-sm border" />
            </div>
        </div>
    </div>
</template>
