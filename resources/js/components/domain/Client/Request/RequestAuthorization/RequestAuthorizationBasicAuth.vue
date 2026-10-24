<script setup lang="ts">
/**
 * @component RequestAuthorizationBasicAuth
 * @description Input fields for Basic Authentication (username/password).
 */
import EnvironmentAwareInput from '@/components/common/EnvironmentAwareInput.vue';
/*
 * Types & Interfaces.
 */

export type RequestAuthorizationBasicAuthModel = {
    username: string;
    password: string;
};

/*
 * Component Setup.
 */

const model = defineModel<RequestAuthorizationBasicAuthModel>({
    default: () => ({
        username: '',
        password: '',
    }),
});
</script>

<template>
    <div class="h-control-lg grid grid-cols-3 border-b">
        <label
            class="px-panel h-control-lg flex items-center border-r py-1 text-xs"
            for="username"
        >
            Username
        </label>
        <EnvironmentAwareInput
            id="username"
            v-model="model.username"
            placeholder="-"
            variant="none"
            class="col-span-2"
            input-class="h-full text-xs"
        />
    </div>
    <div class="h-control-lg grid grid-cols-3 border-b">
        <label
            class="px-panel h-control-lg flex items-center border-r py-1 text-xs"
            for="password"
        >
            Password
        </label>
        <EnvironmentAwareInput
            id="password"
            v-model="model.password"
            type="password"
            placeholder="-"
            variant="none"
            class="col-span-2"
            input-class="h-full text-xs"
        />
    </div>
</template>
