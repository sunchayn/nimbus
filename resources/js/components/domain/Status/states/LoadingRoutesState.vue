<script setup lang="ts">
/**
 * @component LoadingRoutesState
 * @description State displayed while routes are being extracted.
 */
import { RefreshCwIcon } from 'lucide-vue-next';
</script>

<template>
    <div class="flex space-x-2">
        <div class="mt-2">
            <RefreshCwIcon class="size-6 animate-spin" />
        </div>
        <div>
            <h2 class="text-lg font-medium">Loading Routes...</h2>
            <p class="mb-2 text-sm">
                Extracting route information from your application.
            </p>
        </div>
    </div>
</template>
