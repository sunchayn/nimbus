<script setup lang="ts">
/**
 * @component RequestAuthorizationBasicAuth
 * @description Input fields for Basic Authentication (username/password).
 */
import { AppInput } from '@/components/base/input';
import { ref, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export type AppRequestAuthorizationBasicAuthModel = {
    username: string;
    password: string;
};

export interface AppRequestAuthorizationBasicAuthProps {}

export interface AppRequestAuthorizationBasicAuthEmits {
    (e: 'update:modelValue', value: AppRequestAuthorizationBasicAuthModel): void;
}

/*
 * Component Setup.
 */

defineProps<AppRequestAuthorizationBasicAuthProps>();
const emit = defineEmits<AppRequestAuthorizationBasicAuthEmits>();

const model = defineModel<AppRequestAuthorizationBasicAuthModel>({
    default: () => ({
        username: '',
        password: '',
    }),
});

/*
 * State.
 */

const username = ref(model.value.username);
const password = ref(model.value.password);

/*
 * Watchers.
 */

watch(username, newValue => {
    model.value.username = newValue;
    emit('update:modelValue', model.value);
});

watch(password, newValue => {
    model.value.password = newValue;
    emit('update:modelValue', model.value);
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
        <AppInput
            id="username"
            v-model="username"
            placeholder="-"
            class="col-span-2 h-full rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
        />
    </div>
    <div class="grid h-8 grid-cols-3 border-b">
        <label
            class="px-panel flex h-8 items-center border-r py-1 text-xs"
            for="password"
        >
            Password
        </label>
        <AppInput
            id="password"
            v-model="password"
            placeholder="-"
            class="col-span-2 h-full rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
        />
    </div>
</template>
