<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/utils";
import { AppTextarea } from "@/components/base/textarea";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();
</script>

<template>
    <AppTextarea
        data-slot="input-group-control"
        :class="
            cn(
                'flex-1 resize-none rounded-none border-0 bg-transparent py-3 shadow-none focus-visible:ring-0 focus-visible:ring-transparent ring-offset-transparent dark:bg-transparent',
                props.class,
            )
        "
    />
</template>
