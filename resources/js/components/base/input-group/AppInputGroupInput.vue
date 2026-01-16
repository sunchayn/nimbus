<script setup lang="ts">
import { AppInput } from '@/components/base/input';
import { cn } from '@/utils';
import type { HTMLAttributes } from 'vue';
import { ref } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const inputRef = ref<InstanceType<typeof AppInput> | null>(null);

defineExpose({
    focus: () => {
        // AppInput renders a native input element, so we access it via $el
        const inputElement = inputRef.value?.$el as HTMLInputElement | undefined;
        inputElement?.focus();
    },
});
</script>

<template>
    <AppInput
        ref="inputRef"
        data-slot="input-group-control"
        :class="
            cn(
                'flex-1 rounded-none border-0 bg-transparent shadow-none ring-offset-transparent focus-visible:ring-0 focus-visible:ring-transparent dark:bg-transparent',
                props.class,
            )
        "
    />
</template>
