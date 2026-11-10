<script setup lang="ts">
/**
 * @component SessionTool
 * @description Shows the stored session of a session ID or of an encrypted session cookie.
 */
import { AppButton } from '@/components/base/button';
import { AppTextarea } from '@/components/base/textarea';
import { useToolRequest } from '@/composables/toolbox/useToolRequest';
import { shallowRef } from 'vue';
import ToolResult from './ToolResult.vue';

const value = shallowRef('');

const { loading, result, error, run } = useToolRequest('session');
</script>

<template>
    <div class="gap-base flex flex-col" data-testid="session-tool">
        <AppTextarea
            v-model="value"
            placeholder="Session cookie value or session ID"
            :rows="3"
            data-testid="session-value"
        />
        <div>
            <AppButton
                label="Read session"
                :loading="loading"
                data-testid="session-submit"
                @click="run({ value })"
            />
        </div>
        <ToolResult :result="result" :error="error" />
    </div>
</template>
