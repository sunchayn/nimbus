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
    <div class="h-control-lg grid grid-cols-3 border-b">
        <label
            class="px-panel h-control-lg flex items-center border-r py-1 text-xs"
            for="bearer"
        >
            Bearer Token
        </label>
        <EnvironmentAwareInput
            id="bearer"
            v-model="model"
            placeholder="Token"
            variant="none"
            class="col-span-2"
            input-class="h-full text-xs"
        />
    </div>
</template>
