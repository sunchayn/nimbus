<script setup lang="ts">
import type { InputGroupButtonProps } from ".";
import { cn } from "@/utils";
import { AppButton } from "@/components/base/button";
import { inputGroupButtonVariants } from ".";

const props = withDefaults(defineProps<InputGroupButtonProps>(), {
    size: "xs",
    variant: "ghost",
});
</script>

<template>
    <AppButton
        :data-size="props.size"
        :variant="props.variant"
        :class="cn(inputGroupButtonVariants({ size: props.size }), props.class)"
    >
        <slot />
    </AppButton>
</template>
