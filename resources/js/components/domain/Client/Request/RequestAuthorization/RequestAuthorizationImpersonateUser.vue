<script setup lang="ts">
/**
 * @component RequestAuthorizationImpersonateUser
 * @description Input field for user ID to impersonate as authentication.
 */
import { AppInput } from '@/components/base/input';
import { type ModelRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestAuthorizationImpersonateUserProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestAuthorizationImpersonateUserProps>();

const model: ModelRef<number> = defineModel<number>({
    default: () => 0,
});
</script>

<template>
    <div class="grid h-8 grid-cols-3 border-b">
        <label class="px-panel flex h-8 items-center border-r py-1 text-xs" for="user-id">
            User ID
        </label>
        <AppInput
            id="user-id"
            v-model="model"
            placeholder="Pick a user ID to impersonate it."
            class="col-span-2 h-full rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
            type="numeric"
        />
    </div>
</template>
