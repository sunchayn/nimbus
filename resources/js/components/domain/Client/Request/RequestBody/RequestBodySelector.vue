<script setup lang="ts">
/**
 * @component RequestBodySelector
 * @description Dropdown to select the request body type (JSON, FormData, etc).
 */
import {
    AppSelect,
    AppSelectContent,
    AppSelectItem,
    AppSelectTrigger,
    AppSelectValue,
} from '@/components/base/select';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodySelectorProps {
    types: Array<{ id: string; label: string }>;
}

/*
 * Component Setup.
 */

const props = defineProps<AppRequestBodySelectorProps>();

const model = defineModel<string>({
    default: () => '',
});
</script>

<template>
    <div class="flex items-center">
        <span class="text-subtle-foreground w-[80px] text-xs">Payload Type:</span>
        <AppSelect v-model="model">
            <AppSelectTrigger variant="toolbar" class="min-w-[120px] text-xs">
                <AppSelectValue />
            </AppSelectTrigger>
            <AppSelectContent>
                <AppSelectItem
                    v-for="type in props.types"
                    :key="type.id"
                    :value="type.id"
                >
                    {{ type.label }}
                </AppSelectItem>
            </AppSelectContent>
        </AppSelect>
    </div>
</template>
