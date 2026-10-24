<script setup lang="ts">
/**
 * @component ConstDumpRenderer
 * @description Renders a constant value such as null or a boolean in a dump.
 */
import type { ConstDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { styles } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { cn } from '@/utils';

interface PropsShape {
    dump: ConstDump;
}

defineProps<PropsShape>();
</script>

<template>
    <span :class="cn(styles.value, 'italic')">
        {{ String(dump.value) }}
    </span>
</template>
