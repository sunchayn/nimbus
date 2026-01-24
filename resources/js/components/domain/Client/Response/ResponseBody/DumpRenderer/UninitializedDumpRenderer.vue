<script setup lang="ts">
import type { UninitializedDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { styles } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { cn } from '@/utils';

interface PropsShape {
    dump: UninitializedDump;
}

defineProps<PropsShape>();
</script>

<template>
    <span :class="cn(styles.value, 'italic')">
        {{ String(dump.value) }}
        <i class="text-xs">(Uninitialized Prop)</i>
    </span>
</template>
