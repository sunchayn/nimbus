<script setup lang="ts">
import type { HTMLAttributes } from "vue";
import { cn } from "@/utils";
import { AppInput } from "@/components/base/input";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();
</script>

<template>
    <AppInput
        data-slot="input-group-control"
        :class="
            cn(
                'flex-1 rounded-none border-0 bg-transparent focus-visible:ring-0 focus-visible:ring-transparent ring-offset-transparent dark:bg-transparent shadow-none',
                props.class,
            )
        "
    />
</template>
