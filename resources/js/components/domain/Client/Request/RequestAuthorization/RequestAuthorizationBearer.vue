<script setup lang="ts">
/**
 * @component RequestAuthorizationBearer
 * @description Input field for Bearer Token authentication.
 */
import EnvironmentAwareInput from '@/components/common/EnvironmentAwareInput.vue';

/*
 * Component Setup.
 */

const model = defineModel<string>({
    default: '',
});
</script>

<template>
    <div class="grid h-8 grid-cols-3 border-b">
        <label class="px-panel flex h-8 items-center border-r py-1 text-xs" for="bearer">
            Bearer Token
        </label>
        <EnvironmentAwareInput
            id="bearer"
            v-model="model"
            placeholder="Token"
            input-class="col-span-2 h-full rounded-none border-0 text-xs shadow-none focus:ring-0 focus-visible:ring-0"
        />
    </div>
</template>
