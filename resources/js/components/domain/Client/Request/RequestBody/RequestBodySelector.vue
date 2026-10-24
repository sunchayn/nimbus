<script setup lang="ts">
/**
 * @component RequestBodySelector
 * @description Dropdown to select the request body type (JSON, FormData, etc).
 */
import { AppSelect } from '@/components/base/select';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestBodySelectorProps {
    types: Array<{ id: string; label: string }>;
}

/*
 * Component Setup.
 */

const props = defineProps<RequestBodySelectorProps>();

const model = defineModel<string>({
    default: () => '',
});

const items = computed(() =>
    props.types.map(type => ({ label: type.label, value: type.id })),
);
</script>

<template>
    <div class="flex items-center">
        <span class="text-muted w-20 text-xs">Payload Type:</span>
        <AppSelect
            v-model="model"
            :items="items"
            variant="ghost"
            size="sm"
            class="min-w-30"
        />
    </div>
</template>
