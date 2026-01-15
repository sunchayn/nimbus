<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from '@/utils'

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <span
    :class="cn(
      'text-zinc-500 flex items-center gap-2 text-sm [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 dark:text-zinc-400',
      props.class,
    )"
  >
    <slot />
  </span>
</template>
