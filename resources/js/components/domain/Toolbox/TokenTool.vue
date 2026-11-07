<script setup lang="ts">
/**
 * @component TokenTool
 * @description Decodes a JWT without verifying it, or looks up a Sanctum token.
 */
import { AppButton } from '@/components/base/button';
import { AppTextarea } from '@/components/base/textarea';
import { useToolRequest } from '@/composables/toolbox/useToolRequest';
import { shallowRef } from 'vue';
import ToolResult from './ToolResult.vue';

const token = shallowRef('');

const { loading, result, error, run } = useToolRequest('token');

const submit = (type: 'jwt' | 'sanctum') => run({ type, token: token.value });
</script>

<template>
    <div class="gap-base flex flex-col" data-testid="token-tool">
        <AppTextarea
            v-model="token"
            placeholder="JWT or Sanctum plain text token"
            :rows="4"
            data-testid="token-value"
        />
        <div class="gap-base flex">
            <AppButton
                label="Decode JWT"
                :loading="loading"
                data-testid="token-jwt"
                @click="submit('jwt')"
            />
            <AppButton
                label="Find Sanctum token"
                variant="outline"
                :loading="loading"
                data-testid="token-sanctum"
                @click="submit('sanctum')"
            />
        </div>
        <p class="text-muted text-xs">The JWT signature is not verified.</p>
        <ToolResult :result="result" :error="error" />
    </div>
</template>
