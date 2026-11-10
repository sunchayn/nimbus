<script setup lang="ts">
/**
 * @component HashTool
 * @description Makes a hash of a value, or checks a value against a hash.
 */
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import { useToolRequest } from '@/composables/toolbox/useToolRequest';
import { shallowRef } from 'vue';
import ToolResult from './ToolResult.vue';

const value = shallowRef('');
const hash = shallowRef('');

const { loading, result, error, run } = useToolRequest('hash');

const submit = () => run({ value: value.value, hash: hash.value || null });
</script>

<template>
    <div class="gap-base flex flex-col" data-testid="hash-tool">
        <AppInput v-model="value" placeholder="Value" data-testid="hash-value" />
        <AppInput
            v-model="hash"
            placeholder="Hash to check against (leave empty to make a hash)"
            data-testid="hash-hash"
        />
        <div>
            <AppButton
                :label="hash ? 'Check' : 'Make'"
                :loading="loading"
                data-testid="hash-submit"
                @click="submit"
            />
        </div>
        <ToolResult :result="result" :error="error" />
    </div>
</template>
