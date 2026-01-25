<script setup lang="ts">
/**
 * @component RequestBodyFormNone
 * @description Panel indicating an empty or 'none' request body.
 */
import {
    AppPanel,
    AppPanelDescription,
    AppPanelHeader,
    AppPanelTitle,
} from '@/components/base/panel';
import { CircleSlash2Icon } from 'lucide-vue-next';
import { onMounted } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyFormNoneProps {}

export interface AppRequestBodyFormNoneEmits {
    (e: 'update:modelValue', value: null): void;
}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyFormNoneProps>();
const emit = defineEmits<AppRequestBodyFormNoneEmits>();

/*
 * Lifecycle.
 */

onMounted(() => emit('update:modelValue', null));
</script>

<template>
    <AppPanel class="border-b shadow-none">
        <AppPanelHeader class="items-start">
            <CircleSlash2Icon class="size-5 min-w-5" />
            <div class="flex flex-col">
                <AppPanelTitle>No Body</AppPanelTitle>
                <AppPanelDescription>
                    You're performing the request without a Payload.
                </AppPanelDescription>
            </div>
        </AppPanelHeader>
    </AppPanel>
</template>
