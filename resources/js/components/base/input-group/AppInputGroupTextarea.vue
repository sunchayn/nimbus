<script setup lang="ts">
import { AppTextarea } from '@/components/base/textarea';
import { cn } from '@/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <AppTextarea
        data-slot="input-group-control"
        :class="
            cn(
                'flex-1 resize-none rounded-none border-0 bg-transparent py-3 shadow-none ring-offset-transparent focus-visible:ring-0 focus-visible:ring-transparent dark:bg-transparent',
                props.class,
            )
        "
    />
</template>
