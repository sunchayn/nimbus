<script setup lang="ts">
/**
 * @component RequestAuthorizationImpersonateUser
 * @description Input field for the user key to impersonate as authentication. Any key type is accepted.
 */
import GeneratorInput from '@/components/common/GeneratorInput.vue';
import { type ModelRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestAuthorizationImpersonateUserProps {}

/*
 * Component Setup.
 */

defineProps<RequestAuthorizationImpersonateUserProps>();

const model: ModelRef<number | string> = defineModel<number | string>({
    default: () => '',
});
</script>

<template>
    <div class="h-control-lg grid grid-cols-3 border-b">
        <label
            class="px-panel h-control-lg py-tight flex items-center border-r text-xs"
            for="user-id"
        >
            User ID
        </label>
        <GeneratorInput
            id="user-id"
            v-model="model"
            placeholder="Pick a user ID, UUID, or ULID to impersonate it."
            variant="none"
            class="col-span-2 h-full"
            :ui="{ base: 'text-xs' }"
        />
    </div>
</template>
