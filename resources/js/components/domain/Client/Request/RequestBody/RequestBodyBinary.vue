<script setup lang="ts">
/**
 * @component RequestBodyBinary
 * @description File picker for a request body that is sent as a single file.
 */
import { AppButton } from '@/components/base/button';
import { computed } from 'vue';

/*
 * Component Setup.
 */

const model = defineModel<FormData | null>({
    default: () => null,
});

const file = computed(() => {
    const entry = model.value?.get('file');

    return entry instanceof File ? entry : null;
});

/*
 * Actions.
 */

const pickFile = (event: Event) => {
    const picked = (event.target as HTMLInputElement).files?.[0];

    if (!picked) {
        return;
    }

    const formData = new FormData();
    formData.append('file', picked);
    model.value = formData;
};

const clearFile = () => {
    model.value = null;
};
</script>

<template>
    <div class="p-panel gap-base flex flex-col items-start">
        <input
            type="file"
            class="text-xs"
            data-testid="binary-file-input"
            @change="pickFile"
        />
        <p v-if="file" class="text-muted text-xs" data-testid="binary-file-name">
            {{ file.name }}
            <template v-if="file.size === 0">
                (pick the file again after a reload)
            </template>
        </p>
        <AppButton
            v-if="file"
            variant="outline"
            color="neutral"
            size="sm"
            label="Remove file"
            @click="clearFile"
        />
    </div>
</template>
