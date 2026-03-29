<script setup lang="ts">
/**
 * @component RequestAuthorizationBasicAuth
 * @description Input fields for Basic Authentication (username/password).
 */
import EnvironmentAwareInput from '@/components/common/EnvironmentAwareInput.vue';
/*
 * Types & Interfaces.
 */

export type AppRequestAuthorizationBasicAuthModel = {
    username: string;
    password: string;
};

/*
 * Component Setup.
 */

const model = defineModel<AppRequestAuthorizationBasicAuthModel>({
    default: () => ({
        username: '',
        password: '',
    }),
});
</script>

<template>
    <div class="grid h-8 grid-cols-3 border-b">
        <label
            class="px-panel flex h-8 items-center border-r py-1 text-xs"
            for="username"
        >
            Username
        </label>
        <EnvironmentAwareInput
            id="username"
            v-model="model.username"
            placeholder="-"
            input-class="col-span-2 h-full rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
        />
    </div>
    <div class="grid h-8 grid-cols-3 border-b">
        <label
            class="px-panel flex h-8 items-center border-r py-1 text-xs"
            for="password"
        >
            Password
        </label>
        <EnvironmentAwareInput
            id="password"
            v-model="model.password"
            type="password"
            placeholder="-"
            input-class="col-span-2 h-full rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
        />
    </div>
</template>
