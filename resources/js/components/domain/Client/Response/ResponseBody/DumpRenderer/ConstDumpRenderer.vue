<script setup lang="ts">
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
